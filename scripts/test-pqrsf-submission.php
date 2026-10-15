<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/pqrsf.php';
$pdo = db();
// Empty temporary OAuth table prevents email delivery; real requests are untouched.
foreach (['pqrsf', 'google_oauth_tokens'] as $table) {
    $definition = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM)[1];
    $pdo->exec(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $definition));
}
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    '_csrf' => csrf_token(), 'website' => '', 'data_policy' => '1',
    'request_type' => 'petition', 'document_type' => 'OT', 'document_number' => 'TEST12345',
    'full_name' => 'Prueba <script>alert(1)</script>', 'email' => 'test@example.test',
    'phone' => '0000000000', 'subject' => 'Prueba de registro',
    'message' => 'Solicitud de prueba local sin datos personales reales.',
];
$checks = 0;
foreach ([false, true] as $breakNotificationStatus) {
    if ($breakNotificationStatus) $pdo->exec('ALTER TABLE pqrsf DROP COLUMN notification_attempted_at');
    ob_start();
    require __DIR__ . '/../process-pqrsf.php';
    $response = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
    if ($response['success'] !== true || $response['email_sent'] !== false || empty($response['radicado'])) throw new RuntimeException('Saved request reported as failed');
    $checks++;
    $query = $pdo->prepare('SELECT notification_status FROM pqrsf WHERE reference_code = ?');
    $query->execute([$response['radicado']]);
    $status = $query->fetchColumn();
    if ($status !== ($breakNotificationStatus ? 'pending' : 'failed')) throw new RuntimeException('Unexpected request state');
    $checks++;
}
if (!function_exists('pqrsf_notification_message')) throw new RuntimeException('Missing notification builder');
$checks++;
$message = pqrsf_notification_message('TEST-REFERENCE', $_POST);
if (str_contains($message['html'], '<script>') || !str_contains($message['html'], '&lt;script&gt;')) throw new RuntimeException('HTML was not escaped');
$checks++;
if ((int) $pdo->query('SELECT COUNT(*) FROM pqrsf')->fetchColumn() !== 2) throw new RuntimeException('Incorrect number of saved requests');
$checks++;
echo "PASS: $checks PQRSF checks; no emails sent or real requests modified.\n";
