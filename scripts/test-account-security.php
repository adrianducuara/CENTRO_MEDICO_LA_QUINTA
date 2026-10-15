<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/account_security.php';
$pdo = db();
// Temporary tables shadow production tables only within this connection.
foreach (['usuarios', 'admin_security_tokens', 'admin_security_limits'] as $table) {
    $definition = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM)[1];
    $pdo->exec(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $definition));
}
$checks = 0;
function check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    $checks++;
}
$old = 'Luna!Brisa-47';
$new = 'Pino!Nube-85';
$pdo->prepare('INSERT INTO usuarios (id, username, email, password_hash) VALUES (1, ?, ?, ?)')->execute(['security-test', 'old@example.test', password_hash($old, PASSWORD_DEFAULT)]);
$_SESSION['admin_id'] = 1;
$_SESSION['admin_version'] = 0;
check(is_admin(), 'valid session');
check(security_password_error('short', 'short') !== null, 'short password rejected');
check(security_password_error($new, $old) !== null, 'mismatch rejected');
check(security_password_error(str_repeat('a', 73), str_repeat('a', 73)) !== null, 'repetitive password rejected');
$unverified = security_create_token(1, 'reset', 'old@example.test', 0);
check(!security_consume_token($unverified, 'reset', $new), 'unverified email cannot reset');
$verify = security_create_token(1, 'verify', 'new@example.test', 0);
check(!security_consume_token($verify, 'reset', $new), 'purpose bound');
check(security_consume_token($verify, 'verify'), 'email verified');
check(!security_consume_token($verify, 'verify'), 'verification single use');
check(security_user(1)['email'] === 'new@example.test', 'email updated only on verification');
check(!is_admin(), 'email verification revokes sessions');
$expired = security_create_token(1, 'reset', 'new@example.test', 1);
$pdo->exec('UPDATE admin_security_tokens SET expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE)');
check(!security_consume_token($expired, 'reset', $new), 'expired token rejected');
$wrongEmail = security_create_token(1, 'reset', 'wrong@example.test', 1);
check(!security_consume_token($wrongEmail, 'reset', $new), 'email binding');
$stale = security_create_token(1, 'reset', 'new@example.test', 0);
check(!security_consume_token($stale, 'reset', $new), 'stale version rejected');
$reset = security_create_token(1, 'reset', 'new@example.test', 1);
check(security_consume_token($reset, 'reset', $new), 'reset accepted');
check(!security_consume_token($reset, 'reset', $old), 'reset single use');
check(password_verify($new, security_user(1)['password_hash']), 'new password stored');
check(!password_verify($old, security_user(1)['password_hash']), 'old password rejected');
$_SESSION['admin_id'] = 1;
$_SESSION['admin_version'] = 2;
check(is_admin(), 'new session accepted');
$rejected = false;
try { security_change_password(1, 'wrong', $old); } catch (RuntimeException) { $rejected = true; }
check($rejected, 'current password required');
security_change_password(1, $new, $old);
check(!is_admin(), 'password change revokes sessions');
check(password_verify($old, security_user(1)['password_hash']), 'authenticated change succeeds');
check(security_limit('test', 2), 'rate first request');
check(security_limit('test', 2), 'rate second request');
check(!security_limit('test', 2), 'rate limit enforced');
$pdo->exec('UPDATE admin_security_limits SET expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE)');
check(security_limit('test', 2), 'rate limit expires');
$priorUrl = getenv('APP_URL');
putenv('APP_URL=https://clinic.example/sub');
check(security_base_url() === 'https://clinic.example/sub', 'canonical subdirectory');
putenv('APP_URL');
$_SERVER['HTTP_HOST'] = 'attacker.example';
$rejected = false;
try { security_base_url(); } catch (RuntimeException) { $rejected = true; }
check($rejected, 'host injection rejected');
putenv($priorUrl === false ? 'APP_URL' : 'APP_URL=' . $priorUrl);
echo "PASS: $checks account security checks; no real accounts modified.\n";
