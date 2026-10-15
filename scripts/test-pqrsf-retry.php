<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/pqrsf.php';
$pdo = db();
foreach (['pqrsf', 'google_oauth_tokens'] as $table) {
    $definition = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM)[1];
    $pdo->exec(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $definition));
}
$checks = 0;
function check_retry(bool $condition): void {
    global $checks;
    if (!$condition) throw new RuntimeException('Retry regression failed at check ' . ($checks + 1));
    $checks++;
}
$reference = save_pqrsf(['request_type' => 'petition', 'document_type' => 'OT', 'document_number' => 'TEST12345', 'full_name' => 'Local Test', 'email' => 'test@example.test', 'phone' => '0000000000', 'subject' => 'Retry test', 'message' => 'Local test without personal data.']);
$id = (int) $pdo->lastInsertId();
check_retry(retry_pqrsf_notification($id) === 'not_connected');
check_retry($pdo->query('SELECT notification_attempted_at FROM pqrsf')->fetchColumn() === null);
check_retry(claim_pqrsf_notification_retry($id));
check_retry(!claim_pqrsf_notification_retry($id));
$pdo->exec("UPDATE pqrsf SET notification_status = 'failed', notification_attempted_at = DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
check_retry(claim_pqrsf_notification_retry($id));
$pdo->exec("UPDATE pqrsf SET notification_status = 'sent', notification_attempted_at = NULL");
check_retry(!claim_pqrsf_notification_retry($id));
check_retry(retry_pqrsf_notification($id) === 'already_sent');
check_retry(retry_pqrsf_notification($id + 1) === 'not_found');
check_retry((int) $pdo->query('SELECT COUNT(*) FROM pqrsf')->fetchColumn() === 1);
check_retry($pdo->query('SELECT reference_code FROM pqrsf')->fetchColumn() === $reference);
echo "PASS: $checks retry checks; no emails sent or real requests modified.\n";
