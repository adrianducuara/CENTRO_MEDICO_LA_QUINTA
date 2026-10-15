<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/account_security.php';
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    try {
        $allowed = security_limit('recovery-ip:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 10);
        $allowed = security_limit('recovery-email:' . $email, 3) && $allowed;
        if ($allowed && strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $query = db()->prepare('SELECT * FROM usuarios WHERE email = ? AND email_verified_at IS NOT NULL');
            $query->execute([$email]);
            $user = $query->fetch();
            if ($user) security_send_link($user, 'reset', $user['email']);
        }
    } catch (Throwable $exception) { error_log('Recovery request failed: ' . get_class($exception)); }
    flash('success', 'Si el correo corresponde a una cuenta verificada, recibirás un enlace para restablecer tu contraseña. Revisa también la carpeta de spam. Si no llega, espera 15 minutos antes de volver a intentarlo.');
    redirect('forgot-password.php');
}
$publicAuthPage = true;
$pageTitle = 'Recuperar contraseña';
require __DIR__ . '/header.php';
?>
<main class="admin-login"><div class="login-card"><p class="eyebrow">Recuperar acceso</p><h1>Olvidé mi contraseña</h1><p>Ingresa el correo que verificaste en Mi cuenta / Seguridad.</p>
<form method="post"><?= csrf_field() ?><label for="email">Correo de recuperación</label><input id="email" type="email" name="email" autocomplete="email" maxlength="254" required><button class="admin-button admin-button--primary admin-button--full">Enviar enlace</button></form>
<a class="login-back" href="login.php">Volver a iniciar sesión</a></div></main>
<?php require __DIR__ . '/footer.php'; ?>
