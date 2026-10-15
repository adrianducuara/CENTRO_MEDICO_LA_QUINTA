<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/password_policy.php';
$checks = 0;
function policy_check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    $checks++;
}
$valid = ['Luna brisa té', 'Luna mar', ' viento y luna  ', '月🌙山🌲海🍀雲🌻風🌊雨☀', 'brisa luna sol'];
foreach ($valid as $password) policy_check(security_password_error($password, $password) === null, 'accept Unicode/passphrase length');
$maximum = str_repeat('月🌙山🌲海🍀雲🌻', 2);
policy_check(security_password_error($maximum, $maximum) === null, '16 Unicode characters accepted');
policy_check(security_password_error($maximum . 'a', $maximum . 'a') !== null, '17 characters rejected');
policy_check(security_password_error('Luna ma', 'Luna ma') !== null, '7 characters rejected');
foreach (['corta', 'PASSWORD123!', 'Contraseña123', 'aaaaaa montes', 'abcabcabcabc', 'senda 12345678', 'abcd1234 mar sol', 'nubes zyxw mares', 'qwerty la luna'] as $weak) {
    policy_check(security_password_error($weak, $weak) !== null, 'reject weak password');
}
$user = ['username' => 'anderson', 'email' => 'lopezvanegas@example.test', 'full_name' => 'José Vanegas'];
foreach (['ANDERSON luna', 'lopezvanegas mar', 'JOSÉ montaña', 'Vanegas luna'] as $personal) {
    policy_check(security_password_error($personal, $personal, $user) !== null, 'personal information rejected');
}
policy_check(security_password_error('viento luna pino', 'viento luna Pino') !== null, 'exact confirmation required');
policy_check(security_password_error("viento luna \xff", "viento luna \xff") !== null, 'invalid UTF-8 rejected');
foreach ([$maximum, ' viento y luna  ', "luna\0viento y río"] as $password) {
    $hash = security_password_hash($password);
    policy_check(password_get_info($hash)['algoName'] === 'argon2id', 'Argon2id selected');
    policy_check(password_verify($password, $hash), 'full password verifies');
    policy_check(!password_verify($password . 'x', $hash), 'suffix changes rejected');
}
$long = str_repeat('mar y luna ', 8);
$hash = security_password_hash($long . 'A');
policy_check(!password_verify($long . 'B', $hash), 'difference after 72 bytes matters');
$legacy = password_hash('Existing-Password!', PASSWORD_BCRYPT);
policy_check(password_verify('Existing-Password!', $legacy), 'legacy hashes remain compatible');
echo "PASS: $checks password policy and hashing checks.\n";
