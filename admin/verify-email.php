<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/account_security.php';
$token = security_capture_token('verify', 'verify-email.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (security_limit('verify-ip:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 15) && security_consume_token($token, 'verify')) {
            $_SESSION = [];
            session_regenerate_id(true);
            flash('success', 'Correo de recuperación verificado. Inicia sesión nuevamente.');
            redirect('login.php');
        }
        $error = 'El enlace no es válido, ya fue usado o venció. Envía una nueva verificación desde Mi cuenta / Seguridad.';
    } catch (Throwable $exception) { $error = 'No se pudo verificar el correo. Solicita un nuevo enlace desde Mi cuenta / Seguridad.'; }
}
$publicAuthPage = true;
$pageTitle = 'Verificar correo';
require __DIR__ . '/header.php';
?>
<main class="admin-login"><div class="login-card"><h1>Verificar correo de recuperación</h1>
<?php if ($error !== ''): ?><div class="admin-alert admin-alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($token !== ''): ?><p>Confirma que deseas usar este correo para recuperar tu cuenta. Se cerrarán las sesiones abiertas.</p><form method="post" action="verify-email.php"><?= csrf_field() ?><button class="admin-button admin-button--primary admin-button--full">Confirmar correo</button></form><?php else: ?><p>Abre el enlace de verificación que recibiste por correo.</p><?php endif; ?>
<a class="login-back" href="login.php">Volver a iniciar sesión</a></div></main>
<?php require __DIR__ . '/footer.php'; ?>
