<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/password_policy.php';
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$username = $argv[1] ?? '';
$email = $argv[2] ?? '';
$password = getenv('ADMIN_PASSWORD') ?: '';
if (!preg_match('/\A[a-zA-Z0-9_.-]{3,80}\z/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/create-admin.php <usuario> <correo>\n");
    exit(1);
}
$error = security_password_error($password, $password, ['username' => $username, 'email' => $email]);
if ($error !== null) {
    fwrite(STDERR, $error . "\n");
    exit(1);
}

$pdo = db();
if ((int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() !== 0) {
    fwrite(STDERR, "Ya existe un usuario administrador. No se creó ninguna cuenta.\n");
    exit(1);
}

$insert = $pdo->prepare('INSERT INTO usuarios (username, email, password_hash) VALUES (:username, :email, :password_hash)');
$insert->execute([
    'username' => $username,
    'email' => $email,
    'password_hash' => security_password_hash($password),
]);
fwrite(STDOUT, "Cuenta administradora creada para {$username}.\n");
