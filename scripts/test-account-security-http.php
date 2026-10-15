<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/account_security.php';
// Local integration test: a disposable account is always removed in finally.
$base = 'http://127.0.0.1:8000/admin/';
$suffix = bin2hex(random_bytes(8));
$username = 'test_' . $suffix;
$email = $suffix . '@example.test';
$password = 'Luna!Brisa-47';
$replacement = 'Pino!Nube-85';
$id = null;
$browser = curl_init();
curl_setopt_array($browser, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 10]);
function request(string $page, ?array $data = null): array {
    global $browser, $base;
    curl_setopt($browser, CURLOPT_URL, $base . $page);
    curl_setopt($browser, CURLOPT_POST, $data !== null);
    if ($data !== null) curl_setopt($browser, CURLOPT_POSTFIELDS, http_build_query($data));
    $body = curl_exec($browser);
    if ($body === false) throw new RuntimeException(curl_error($browser));
    return ['body' => $body, 'url' => curl_getinfo($browser, CURLINFO_EFFECTIVE_URL), 'status' => curl_getinfo($browser, CURLINFO_RESPONSE_CODE)];
}
function csrf(array $response): string {
    preg_match('/name="_csrf" value="([^"]+)"/', $response['body'], $match);
    if (empty($match[1])) throw new RuntimeException('Missing CSRF');
    return $match[1];
}
function expect(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    echo 'PASS: ' . $label . PHP_EOL;
}
try {
    db()->prepare('INSERT INTO usuarios (username, email, password_hash) VALUES (?, ?, ?)')->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $id = (int) db()->lastInsertId();
    $response = request('login.php');
    $response = request('login.php', ['_csrf' => csrf($response), 'username' => $username, 'password' => $password]);
    expect(str_ends_with($response['url'], '/index.php'), 'login');
    $response = request('account.php');
    expect(str_contains($response['body'], 'Correo de recuperación'), 'account settings');
    $response = request('account.php', ['action' => 'password', 'current_password' => $password, 'password' => $replacement, 'confirmation' => $replacement]);
    expect($response['status'] === 400, 'CSRF required');
    $response = request('account.php');
    $response = request('account.php', ['_csrf' => csrf($response), 'action' => 'password', 'current_password' => $password, 'password' => $replacement, 'confirmation' => $replacement]);
    expect(str_ends_with($response['url'], '/login.php'), 'password change signs out');
    expect(password_verify($replacement, security_user($id)['password_hash']), 'password persisted');
    $token = security_create_token($id, 'verify', $email, 1);
    $response = request('verify-email.php?token=' . $token);
    expect(!str_contains($response['url'], '?'), 'verification token removed from URL');
    expect(security_user($id)['email_verified_at'] === null, 'GET does not verify email');
    $response = request('verify-email.php', ['_csrf' => csrf($response)]);
    expect(security_user($id)['email_verified_at'] !== null, 'email verification form');
    $token = security_create_token($id, 'reset', $email, 2);
    $response = request('reset-password.php?token=' . $token);
    expect(!str_contains($response['url'], '?'), 'reset token removed from URL');
    $response = request('reset-password.php', ['_csrf' => csrf($response), 'password' => $password, 'confirmation' => $password]);
    expect(str_ends_with($response['url'], '/login.php') && password_verify($password, security_user($id)['password_hash']), 'reset password form');
    expect(!security_consume_token($token, 'reset', $replacement), 'HTTP reset token cannot be reused');
    $response = request('forgot-password.php');
    $response = request('forgot-password.php', ['_csrf' => csrf($response), 'email' => 'absent-' . $email]);
    expect(str_contains($response['body'], 'Si el correo corresponde'), 'generic recovery response');
} finally {
    if ($id !== null) {
        db()->prepare('DELETE FROM admin_security_tokens WHERE user_id = ?')->execute([$id]);
        db()->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        foreach (['account:' . $id, 'login-user:' . $username, 'recovery-email:absent-' . $email] as $key) db()->prepare('DELETE FROM admin_security_limits WHERE bucket_hash = ?')->execute([hash('sha256', $key)]);
    }
    curl_close($browser);
}
