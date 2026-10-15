<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/account_security.php';
$token = security_capture_token('reset', 'reset-password.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    $error = security_password_error($password, (string) ($_POST['confirmation'] ?? '')) ?? '';
    if ($error === '') {
        try {
            if (security_limit('reset-ip:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 15) && security_consume_token($token, 'reset', $password)) {
                $_SESSION = [];
                session_regenerate_id(true);
                flash('success', 'Contraseña restablecida. Inicia sesión con tu nueva contraseña.');
                redirect('login.php');
            }
            $error = 'El enlace no es válido, ya fue usado o venció. Solicita un nuevo enlace.';
        } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
        catch (Throwable $exception) { $error = 'No fue posible restablecer la contraseña. Intenta nuevamente.'; }
    }
}
$publicAuthPage = true;
$pageTitle = 'Restablecer contraseña';
require __DIR__ . '/header.php';
?>
<main class="admin-login"><div class="login-card"><h1>Restablecer contraseña</h1>
<?php if ($error !== ''): ?><div class="admin-alert admin-alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($token !== ''): ?><form method="post" action="reset-password.php"><?= csrf_field() ?><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" autocomplete="new-password" required aria-describedby="password-help password-feedback"><label for="confirmation">Confirmar contraseña</label><input id="confirmation" name="confirmation" type="password" autocomplete="new-password" required aria-describedby="confirmation-feedback"><?php password_policy_feedback(); ?><button class="admin-button admin-button--primary admin-button--full">Guardar contraseña</button></form>
<?php else: ?><p>Abre el enlace que recibiste por correo para elegir tu nueva contraseña.</p><?php endif; ?>
<a class="login-back" href="forgot-password.php">Solicitar un nuevo enlace</a><a class="login-back" href="login.php">Volver a iniciar sesión</a></div></main>
<?php require __DIR__ . '/footer.php'; ?>
