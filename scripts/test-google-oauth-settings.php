<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/google_oauth.php';

// Temporary table and an in-memory encryption key isolate all saved test values.
$pdo = db();
$definition = $pdo->query('SHOW CREATE TABLE google_oauth_tokens')->fetch(PDO::FETCH_NUM)[1];
$pdo->exec(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $definition));
$originalKey = getenv('APP_ENCRYPTION_KEY');
putenv('APP_ENCRYPTION_KEY=' . base64_encode(random_bytes(32)));
$checks = 0;
function expect_setting(bool $condition, string $label): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    $checks++;
}
function seed_authorization(): void {
    db()->prepare("UPDATE google_oauth_tokens SET access_token_encrypted = ?, refresh_token_encrypted = ?, authorized_email = sender_email, connection_status = 'verified', token_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR), last_verified_at = NOW() WHERE id = 1")
        ->execute([google_oauth_encrypt('test-access'), google_oauth_encrypt('test-refresh')]);
}
try {
    expect_setting(!$pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES), 'native prepares enabled');
    google_oauth_save_settings('first.apps.googleusercontent.com', 'test-secret', 'sender@example.test', 'recipient@example.test');
    $saved = google_oauth_settings();
    expect_setting($saved['client_id'] === 'first.apps.googleusercontent.com', 'insert client ID');
    expect_setting($saved['client_secret'] === 'test-secret', 'secret decrypts');
    expect_setting($saved['client_secret_encrypted'] !== 'test-secret', 'secret encrypted at rest');
    seed_authorization();
    google_oauth_save_settings('first.apps.googleusercontent.com', '', 'sender@example.test', 'new-recipient@example.test');
    $saved = google_oauth_settings();
    expect_setting($saved['recipient_email'] === 'new-recipient@example.test', 'update recipient');
    expect_setting($saved['client_secret'] === 'test-secret', 'blank preserves secret');
    expect_setting($saved['connection_status'] === 'verified' && $saved['refresh_token'] === 'test-refresh', 'recipient change preserves connection');
    $rejected = false;
    try { google_oauth_save_settings('second.apps.googleusercontent.com', '', 'sender@example.test', 'recipient@example.test'); }
    catch (InvalidArgumentException $exception) { $rejected = str_contains($exception->getMessage(), 'Client Secret'); }
    expect_setting($rejected, 'new client requires matching secret');
    expect_setting(google_oauth_settings()['client_id'] === 'first.apps.googleusercontent.com', 'invalid change does not save');
    google_oauth_save_settings('second.apps.googleusercontent.com', 'replacement-secret', 'sender@example.test', 'recipient@example.test');
    $saved = google_oauth_settings();
    expect_setting($saved['client_id'] === 'second.apps.googleusercontent.com' && $saved['client_secret'] === 'replacement-secret', 'replace credentials');
    expect_setting($saved['connection_status'] === 'pending', 'credential change requires authorization');
    foreach (['access_token', 'refresh_token', 'token_expires_at', 'authorized_email', 'last_verified_at'] as $field) {
        expect_setting($saved[$field] === null, 'clear stale ' . $field);
    }
    seed_authorization();
    google_oauth_save_settings('second.apps.googleusercontent.com', '', 'different@example.test', 'recipient@example.test');
    $saved = google_oauth_settings();
    expect_setting($saved['connection_status'] === 'pending' && $saved['refresh_token'] === null, 'sender change requires authorization');
    expect_setting($saved['client_secret'] === 'replacement-secret', 'sender change preserves client secret');
    echo "PASS: $checks Google settings checks; real credentials unchanged.\n";
} finally {
    putenv($originalKey === false ? 'APP_ENCRYPTION_KEY' : 'APP_ENCRYPTION_KEY=' . $originalKey);
}
