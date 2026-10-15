<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/google_oauth.php';
require_once __DIR__ . '/../includes/gmail_api.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save') {
        $clientId = trim((string) ($_POST['client_id'] ?? ''));
        $clientSecret = trim((string) ($_POST['client_secret'] ?? ''));
        $sender = trim((string) ($_POST['sender_email'] ?? ''));
        $recipient = trim((string) ($_POST['recipient_email'] ?? ''));
        if ($clientId === '' || text_length($clientId) > 255 || !filter_var($sender, FILTER_VALIDATE_EMAIL) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Ingresa un Client ID y correos electrónicos válidos.');
        } else {
            try {
                google_oauth_save_settings($clientId, $clientSecret, $sender, $recipient);
                flash('success', 'La configuración de Google se guardó de forma cifrada.');
            } catch (InvalidArgumentException $exception) {
                flash('error', $exception->getMessage());
            } catch (Throwable $exception) {
                error_log('No fue posible guardar OAuth Google: ' . $exception->getMessage());
                flash('error', 'No fue posible guardar la configuración de Google.');
            }
        }
        redirect('configuracion-api.php');
    }
    if ($action === 'disconnect') {
        google_oauth_disconnect();
        flash('success', 'La cuenta de Google fue desconectada y los tokens se eliminaron.');
        redirect('configuracion-api.php');
    }
    if ($action === 'test') {
        $sent = gmail_api_send_test_email();
        flash($sent ? 'success' : 'error', $sent ? 'Correo de prueba enviado correctamente.' : 'No fue posible enviar el correo de prueba. Revisa la conexión OAuth.');
        redirect('configuracion-api.php');
    }
    http_response_code(400);
    exit('Acción no válida.');
}

$settings = google_oauth_settings();
$connectionState = google_oauth_connection_state();
$connected = $connectionState === 'verified';
$statusLabels = ['unconfigured' => 'Sin configurar', 'pending' => 'Pendiente de autorización', 'authorized' => 'Autorizado: falta prueba de Gmail', 'verified' => 'Conectado y verificado', 'reauth_required' => 'Sesión expirada: requiere reconexión', 'error' => 'Error de conexión'];
$statusLabel = $statusLabels[$connectionState] ?? 'Pendiente de autorización';
$redirectUri = null;
$redirectUriError = null;
try {
    $redirectUri = google_oauth_redirect_uri();
} catch (Throwable $exception) {
    error_log('Configuración de URI OAuth incompleta: ' . $exception->getMessage());
    $redirectUriError = 'Activa HTTPS y define la URI de redirección antes de conectar Google.';
}
$pageTitle = 'Google Cloud y Gmail';
require __DIR__ . '/header.php';
?>
<div class="admin-content">
    <div class="admin-heading"><div><a class="admin-back" href="index.php">← Panel</a><p class="eyebrow">Integraciones</p><h1>Google Cloud y Gmail</h1><p>Autoriza el envío seguro de notificaciones de PQRSF desde la cuenta institucional.</p></div></div>
    <div class="admin-columns admin-columns--oauth">
        <section class="admin-panel oauth-status-panel">
            <p class="eyebrow">Estado de conexión</p>
            <span class="oauth-status <?= $connected ? 'oauth-status--connected' : 'oauth-status--disconnected' ?>"><?= e($statusLabel) ?></span>
            <h2><?= e((string) ($settings['authorized_email'] ?? $settings['sender_email'] ?? 'Conecta la cuenta institucional')) ?></h2>
            <p class="panel-description">URI de redirección autorizada en Google Cloud:</p>
            <?php if ($redirectUri !== null): ?><code class="oauth-redirect-uri"><?= e($redirectUri) ?></code>
            <p class="panel-description">Copia esta URL exactamente en Google Cloud Console → Credenciales → URI de redirección autorizados.</p>
            <?php else: ?><p class="form-alert"><?= e($redirectUriError) ?></p><?php endif; ?>
            <?php if ($redirectUri !== null && $settings !== null && !empty($settings['client_id']) && !empty($settings['client_secret'])): ?>
                <?php if (in_array($connectionState, ['pending', 'reauth_required', 'error'], true)): ?><form method="post" action="google-oauth-start.php"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="admin-button admin-button--primary" type="submit">Conectar con Google / Gmail</button></form><?php else: ?>
                <div class="form-actions"><form method="post"><input type="hidden" name="action" value="test"><?= csrf_field() ?><button class="admin-button admin-button--primary" type="submit">Probar envío de correo</button></form><form method="post" data-confirm="¿Desconectar Gmail? Los tokens guardados se eliminarán."><input type="hidden" name="action" value="disconnect"><?= csrf_field() ?><button class="admin-button admin-button--quiet" type="submit">Desconectar cuenta</button></form></div>
                <?php endif; ?>
            <?php else: ?><p class="form-alert">Guarda primero el Client ID y Client Secret para habilitar la conexión.</p><?php endif; ?>
        </section>
        <section class="admin-panel">
            <h2>Configuración básica</h2><p class="panel-description">El Client Secret y los tokens se cifran antes de guardarse. Nunca se vuelven a mostrar en pantalla.</p>
            <form method="post" action="configuracion-api.php"><input type="hidden" name="action" value="save"><?= csrf_field() ?>
                <label for="client_id">Google OAuth Client ID</label><input id="client_id" name="client_id" maxlength="255" value="<?= e((string) ($settings['client_id'] ?? '')) ?>" autocomplete="off" required>
                <label for="client_secret">Google OAuth Client Secret <?= $settings !== null ? '<span class="field-hint">Déjalo vacío para conservarlo</span>' : '' ?></label><input id="client_secret" name="client_secret" type="password" maxlength="255" autocomplete="new-password" <?= $settings === null ? 'required' : '' ?>>
                <label for="sender_email">Correo remitente</label><input id="sender_email" name="sender_email" type="email" maxlength="254" value="<?= e((string) ($settings['sender_email'] ?? 'direccionadm.cmq@gmail.com')) ?>" required>
                <label for="recipient_email">Correo receptor</label><input id="recipient_email" name="recipient_email" type="email" maxlength="254" value="<?= e((string) ($settings['recipient_email'] ?? 'direccionadm.cmq@gmail.com')) ?>" required>
                <div class="form-actions"><button class="admin-button admin-button--primary" type="submit">Guardar configuración</button></div>
            </form>
        </section>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
