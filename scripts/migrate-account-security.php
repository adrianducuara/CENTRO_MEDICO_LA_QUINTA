<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
$pdo = db();
$columns = $pdo->query('SHOW COLUMNS FROM usuarios')->fetchAll(PDO::FETCH_COLUMN);
foreach (['email_verified_at' => 'DATETIME NULL', 'session_version' => 'INT UNSIGNED NOT NULL DEFAULT 0'] as $name => $definition) {
    if (!in_array($name, $columns, true)) $pdo->exec("ALTER TABLE usuarios ADD COLUMN $name $definition");
}
$pdo->exec(file_get_contents(__DIR__ . '/../config/account-security.sql'));
echo "Account security migration complete.\n";
