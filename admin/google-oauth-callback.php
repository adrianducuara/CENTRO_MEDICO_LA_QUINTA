<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/google_oauth.php';

require_admin();

$expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');
$verifier = (string) ($_SESSION['google_oauth_verifier'] ?? '');
$issuedAt = (int) ($_SESSION['google_oauth_state_issued_at'] ?? 0);
$returnedState = (string) ($_GET['state'] ?? '');
unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_verifier'], $_SESSION['google_oauth_state_issued_at']);

if ($expectedState === '' || $verifier === '' || $returnedState === '' || !hash_equals($expectedState, $returnedState) || $issuedAt < (time() - 600)) {
    flash('error', 'La verificación de seguridad de Google expiró o no es válida. Vuelva a iniciar la conexión.');
    redirect('configuracion-api.php');
}

if (!empty($_GET['error'])) {
    $reason = (string) ($_GET['error_description'] ?? $_GET['error']);
    error_log('Google OAuth fue cancelado o rechazado: ' . $reason);
    flash('error', 'Google no autorizó la conexión. Puede intentarlo nuevamente desde el panel.');
    redirect('configuracion-api.php');
}

$code = trim((string) ($_GET['code'] ?? ''));
if ($code === '') {
    flash('error', 'Google no devolvió un código de autorización válido.');
    redirect('configuracion-api.php');
}

try {
    $settings = google_oauth_settings();
    if (!$settings || empty($settings['client_id']) || empty($settings['client_secret'])) {
        throw new RuntimeException('No hay credenciales de cliente configuradas.');
    }

    $tokenData = google_oauth_exchange_code($settings, $code, $verifier);
    $email = google_oauth_verified_email($settings, $tokenData);
    google_oauth_save_tokens($tokenData, $email);
    flash('success', 'Cuenta autorizada. Envía una prueba de correo para completar la verificación Gmail.');
} catch (Firebase\JWT\BeforeValidException $exception) {
    error_log('Google OAuth: el reloj del servidor está atrasado respecto al token de Google.');
    flash('error', 'La hora del servidor no está sincronizada con Google. Sincroniza la fecha y hora del servidor y vuelve a conectar.');
} catch (Throwable $exception) {
    error_log('Error en callback OAuth de Google: ' . $exception->getMessage());
    flash('error', 'No se pudo completar la conexión con Google. Revise las credenciales, la URI de redirección y los registros del servidor.');
}

redirect('configuracion-api.php');
