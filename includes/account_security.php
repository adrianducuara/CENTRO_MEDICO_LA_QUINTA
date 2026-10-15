<?php
declare(strict_types=1);
require_once __DIR__ . '/password_policy.php';

function security_limit(string $key, int $maximum = 5): bool
{
    $pdo = db();
    $hash = hash('sha256', $key);
    $pdo->exec('DELETE FROM admin_security_limits WHERE expires_at < NOW()');
    $pdo->prepare('INSERT INTO admin_security_limits (bucket_hash, attempts, expires_at) VALUES (?, 1, DATE_ADD(NOW(), INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE attempts = attempts + 1')->execute([$hash]);
    $query = $pdo->prepare('SELECT attempts FROM admin_security_limits WHERE bucket_hash = ?');
    $query->execute([$hash]);
    return (int) $query->fetchColumn() <= $maximum;
}

function security_base_url(): string
{
    $url = rtrim(trim((string) getenv('APP_URL')), '/');
    if ($url === '') {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if (!preg_match('/\A(?:127\.0\.0\.1|localhost)(?::[0-9]+)?\z/', $host)) throw new RuntimeException('Configura APP_URL con la URL HTTPS del sitio.');
        $url = 'http://' . $host;
    }
    $parts = parse_url($url);
    if ($parts === false || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) ||
        (($parts['scheme'] ?? '') !== 'https' && !(($parts['scheme'] ?? '') === 'http' && in_array($parts['host'], ['localhost', '127.0.0.1'], true)))) {
        throw new RuntimeException('APP_URL debe ser una URL HTTPS válida.');
    }
    return $url;
}

function security_user(int $id, bool $lock = false): array
{
    $query = db()->prepare('SELECT * FROM usuarios WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''));
    $query->execute([$id]);
    $user = $query->fetch();
    if (!$user) throw new RuntimeException('La cuenta no está disponible.');
    return $user;
}

function security_create_token(int $id, string $purpose, string $email, int $version): string
{
    $token = bin2hex(random_bytes(32));
    db()->exec('DELETE FROM admin_security_tokens WHERE expires_at < NOW()');
    db()->prepare('INSERT INTO admin_security_tokens (token_hash, user_id, purpose, email, session_version, expires_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')->execute([hash('sha256', $token), $id, $purpose, $email, $version]);
    return $token;
}

function security_send_link(array $user, string $purpose, string $email): bool
{
    require_once __DIR__ . '/gmail_api.php';
    $base = security_base_url();
    $token = security_create_token((int) $user['id'], $purpose, $email, (int) $user['session_version']);
    $page = $purpose === 'reset' ? 'reset-password.php' : 'verify-email.php';
    $url = $base . '/admin/' . $page . '?token=' . $token;
    $subject = $purpose === 'reset' ? 'Restablecer contraseña' : 'Verificar correo de recuperación';
    $html = '<h2>' . e($subject) . '</h2><p>Centro Médico La Quinta</p><p><a href="' . e($url) . '">' . e($subject) . '</a></p><p>Este enlace vence en 30 minutos y solo se puede usar una vez. Si no solicitaste este cambio, ignora este correo.</p>';
    try {
        $result = gmail_api_send_message($subject, $html, null, $email);
        if ($result['ok']) return true;
    } catch (Throwable $exception) {
        error_log('Account recovery delivery failed: ' . get_class($exception));
    }
    db()->prepare('DELETE FROM admin_security_tokens WHERE token_hash = ?')->execute([hash('sha256', $token)]);
    return false;
}

function security_change_password(int $id, string $current, string $password): void
{
    $error = security_password_error($password, $password);
    if ($error !== null) throw new RuntimeException($error);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $user = security_user($id, true);
        $error = security_password_error($password, $password, $user);
        if ($error !== null) throw new InvalidArgumentException($error);
        if ((int) $user['session_version'] !== (int) ($_SESSION['admin_version'] ?? -1) || !password_verify($current, $user['password_hash'])) throw new RuntimeException('La contraseña actual no es correcta o la sesión expiró.');
        $pdo->prepare('UPDATE usuarios SET password_hash = ?, session_version = session_version + 1 WHERE id = ?')->execute([security_password_hash($password), $id]);
        $pdo->prepare('DELETE FROM admin_security_tokens WHERE user_id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $exception) { $pdo->rollBack(); throw $exception; }
}

function security_consume_token(string $token, string $purpose, ?string $password = null): bool
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) return false;
    $pdo = db();
    $hash = hash('sha256', $token);
    $lookup = $pdo->prepare('SELECT user_id FROM admin_security_tokens WHERE token_hash = ? AND purpose = ?');
    $lookup->execute([$hash, $purpose]);
    $id = $lookup->fetchColumn();
    if ($id === false) return false;
    $pdo->beginTransaction();
    try {
        $user = security_user((int) $id, true);
        $query = $pdo->prepare('SELECT * FROM admin_security_tokens WHERE token_hash = ? AND purpose = ? AND expires_at > NOW() FOR UPDATE');
        $query->execute([$hash, $purpose]);
        $row = $query->fetch();
        if (!$row || (int) $row['session_version'] !== (int) $user['session_version']) { $pdo->rollBack(); return false; }
        if ($purpose === 'reset') {
            if ($password === null || security_password_error($password, $password) !== null || !$user['email_verified_at'] || strcasecmp($row['email'], $user['email']) !== 0) { $pdo->rollBack(); return false; }
            $error = security_password_error($password, $password, $user);
            if ($error !== null) throw new InvalidArgumentException($error);
            $pdo->prepare('UPDATE usuarios SET password_hash = ?, session_version = session_version + 1 WHERE id = ?')->execute([security_password_hash($password), $id]);
        } elseif ($purpose === 'verify') {
            $pdo->prepare('UPDATE usuarios SET email = ?, email_verified_at = NOW(), session_version = session_version + 1 WHERE id = ?')->execute([$row['email'], $id]);
        } else { $pdo->rollBack(); return false; }
        $pdo->prepare('DELETE FROM admin_security_tokens WHERE user_id = ?')->execute([$id]);
        $pdo->commit();
        return true;
    } catch (Throwable $exception) { $pdo->rollBack(); throw $exception; }
}

// Keep bearer tokens out of subsequent URLs, browser referrers and form actions.
function security_capture_token(string $purpose, string $page): string
{
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
    if (isset($_GET['token'])) {
        $_SESSION['security_' . $purpose] = is_string($_GET['token']) ? $_GET['token'] : '';
        redirect($page);
    }
    return (string) ($_SESSION['security_' . $purpose] ?? '');
}
