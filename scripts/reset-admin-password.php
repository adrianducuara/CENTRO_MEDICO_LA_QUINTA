<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/password_policy.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$username = $argv[1] ?? '';
$password = getenv('ADMIN_PASSWORD') ?: '';
if (!preg_match('/\A[a-zA-Z0-9_.-]{3,80}\z/', $username)) {
    fwrite(STDERR, "Uso: php scripts/reset-admin-password.php <usuario>\n");
    exit(1);
}
$query = db()->prepare('SELECT * FROM usuarios WHERE username = ?');
$query->execute([$username]);
$user = $query->fetch();
if (!$user) { fwrite(STDERR, "No se encontró la cuenta.\n"); exit(1); }
$error = security_password_error($password, $password, $user);
if ($error !== null) {
    fwrite(STDERR, $error . "\n");
    exit(1);
}

$update = db()->prepare('UPDATE usuarios SET password_hash = :password_hash, session_version = session_version + 1 WHERE username = :username');
$update->execute([
    'password_hash' => security_password_hash($password),
    'username' => $username,
]);
if ($update->rowCount() !== 1) {
    fwrite(STDERR, "No se encontró la cuenta administradora indicada.\n");
    exit(1);
}

fwrite(STDOUT, "Contraseña actualizada para {$username}.\n");
