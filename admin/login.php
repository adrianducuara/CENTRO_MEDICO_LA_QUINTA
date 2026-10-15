<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/account_security.php';
header('Cache-Control: no-store');
if (is_admin()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $allowed = security_limit('login-ip:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 30);
    $allowed = security_limit('login-user:' . strtolower($username), 15) && $allowed;
    $query = db()->prepare('SELECT id, username, password_hash, session_version FROM usuarios WHERE username = :username LIMIT 1');
    $query->execute(['username' => $username]);
    $user = $query->fetch();
    if ($allowed && $user !== false && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_version'] = (int) $user['session_version'];
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        redirect('index.php');
    }
    $error = 'Usuario o contraseña incorrectos.';
}
$pageTitle = 'Iniciar sesión';
require __DIR__ . '/header.php';
?>
<main class="admin-login">
    <div class="login-card">
        <a class="admin-brand" href="../index.php">Centro Médico <span>La Quinta</span></a>
        <p class="eyebrow">Área privada</p><h1>Iniciar sesión</h1><p class="login-intro">Accede para administrar las publicaciones y documentos del centro médico.</p>
        <?php if ($error !== ''): ?><div class="admin-alert admin-alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="login.php">
            <?= csrf_field() ?>
            <label for="username">Usuario</label><input id="username" name="username" type="text" autocomplete="username" maxlength="80" required>
            <label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="admin-button admin-button--primary admin-button--full" type="submit">Entrar al panel <span aria-hidden="true">→</span></button>
        </form>
        <a class="login-back" href="forgot-password.php">Olvidé mi contraseña</a>
        <a class="login-back" href="../index.php">← Volver al sitio</a>
    </div>
</main>
<?php require __DIR__ . '/footer.php'; ?>
