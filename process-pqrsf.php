<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/pqrsf.php';

header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Método no permitido.']);
    exit;
}

verify_csrf();
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Solicitud no válida.']);
    exit;
}

$values = [];
foreach (['request_type', 'document_type', 'document_number', 'full_name', 'email', 'phone', 'subject', 'message'] as $field) {
    $values[$field] = trim((string) ($_POST[$field] ?? ''));
}
$errors = validate_pqrsf_values($values, ($_POST['data_policy'] ?? '') === '1');
if ($errors !== []) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Revisa los campos señalados antes de enviar la solicitud.', 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $reference = save_pqrsf($values);
} catch (Throwable $exception) {
    error_log('No fue posible guardar la PQRSF: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'No fue posible registrar la solicitud. Inténtalo de nuevo.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// A notification failure must never turn a saved request into a submission error.
$emailSent = false;
try {
    $emailSent = notify_pqrsf($reference, $values);
} catch (Throwable $exception) {
    error_log('Falló la notificación de la PQRSF registrada ' . $reference . ': ' . get_class($exception));
}
echo json_encode(['status' => 'success', 'success' => true, 'radicado' => $reference, 'reference' => $reference, 'email_sent' => $emailSent], JSON_UNESCAPED_UNICODE);
