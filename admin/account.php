<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/account_security.php';
require_once __DIR__ . '/../includes/google_oauth.php';
require_admin();
header('Cache-Control: no-store');
$user = security_user($_SESSION['admin_id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (!security_limit('account:' . $user['id'], 10)) throw new RuntimeException('Demasiados intentos. Intenta nuevamente en 15 minutos.');
        $current = (string) ($_POST['current_password'] ?? '');
        if (!password_verify($current, $user['password_hash'])) throw new RuntimeException('La contraseña actual no es correcta.');
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'password') {
            $password = (string) ($_POST['password'] ?? '');
            $error = security_password_error($password, (string) ($_POST['confirmation'] ?? ''), $user);
            if ($error !== null) throw new RuntimeException($error);
            security_change_password((int) $user['id'], $current, $password);
            $_SESSION = [];
            session_regenerate_id(true);
            flash('success', 'Contraseña actualizada. Inicia sesión con tu nueva contraseña.');
            redirect('login.php');
        }
        if ($action !== 'email') throw new RuntimeException('Acción no válida.');
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Ingresa un correo válido.');
        $exists = db()->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ?');
        $exists->execute([$email, $user['id']]);
        if ($exists->fetchColumn()) throw new RuntimeException('Ese correo no está disponible.');
        if (!google_oauth_is_connected()) throw new RuntimeException('Conecta y verifica Google / Gmail antes de verificar tu correo.');
        if (!security_send_link($user, 'verify', $email)) throw new RuntimeException('No se pudo enviar el correo. Revisa Google / Gmail e intenta de nuevo.');
        flash('success', 'Enviamos un enlace al correo indicado. Confírmalo en los próximos 30 minutos. Tu correo actual se conserva hasta completar la verificación.');
    } catch (RuntimeException | InvalidArgumentException $exception) {
        flash('error', $exception instanceof PDOException ? 'No fue posible guardar el cambio. Intenta nuevamente.' : $exception->getMessage());
    }
    redirect('account.php');
}
$connected = google_oauth_is_connected();
$pageTitle = 'Mi cuenta / Seguridad';
require __DIR__ . '/header.php';
?>
<main class="admin-content">
    <div class="admin-heading"><div><p class="eyebrow">Mi cuenta</p><h1>Seguridad</h1><p>Administra tu contraseña y el correo que usarás para recuperar el acceso.</p></div></div>
    <?php if (!$connected): ?><div class="admin-alert admin-alert--error">El envío de correo aún no está disponible. <a href="configuracion-api.php">Conecta y verifica Google / Gmail</a> para activar la recuperación.</div><?php endif; ?>
    <div class="admin-columns">
        <section class="admin-panel"><h2>Cambiar contraseña</h2><p>Al guardar, se cerrarán todas las sesiones y tendrás que iniciar sesión nuevamente.</p>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
                <label for="current-password">Contraseña actual</label><input id="current-password" type="password" name="current_password" autocomplete="current-password" required>
                <label for="new-password">Nueva contraseña</label><input id="new-password" type="password" name="password" autocomplete="new-password" required aria-describedby="password-help password-feedback">
                <label for="confirmation">Confirmar contraseña</label><input id="confirmation" type="password" name="confirmation" autocomplete="new-password" required aria-describedby="confirmation-feedback">
                <?php password_policy_feedback($user); ?>
                <div class="form-actions"><button class="admin-button admin-button--primary">Guardar contraseña</button></div>
            </form>
        </section>
        <section class="admin-panel"><h2>Correo de recuperación</h2><p>Correo guardado: <strong><?= e($user['email']) ?></strong></p><p><?= $user['email_verified_at'] ? 'Verificado. Puedes usarlo para recuperar tu contraseña.' : 'Sin verificar. Confirma tu correo para activar la recuperación.' ?></p>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="email">
                <label for="email">Correo electrónico</label><input id="email" type="email" name="email" maxlength="254" autocomplete="email" value="<?= e($user['email']) ?>" required>
                <label for="email-password">Contraseña actual</label><input id="email-password" type="password" name="current_password" autocomplete="current-password" required>
                <p>Enviaremos un enlace para verificar la dirección. El enlace vence en 30 minutos. Al confirmar, se cerrarán las sesiones abiertas.</p>
                <div class="form-actions"><button class="admin-button admin-button--primary" <?= !$connected ? 'disabled' : '' ?>>Enviar verificación</button></div>
            </form>
        </section>
    </div>
</main>
<?php require __DIR__ . '/footer.php'; ?>
