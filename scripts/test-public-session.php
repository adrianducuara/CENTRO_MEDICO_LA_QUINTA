<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

function public_session_test(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FALLO: {$message}\n");
        exit(1);
    }
    echo "OK: {$message}\n";
}

public_session_test(
    session_status() !== PHP_SESSION_ACTIVE,
    'bootstrap no inicia una sesión para una respuesta pública anónima'
);
public_session_test(flash() === null, 'consultar un aviso no inicia una sesión pública');
public_session_test(
    session_status() !== PHP_SESSION_ACTIVE,
    'la consulta de aviso mantiene la respuesta pública sin sesión'
);

$token = csrf_token();
public_session_test(session_status() === PHP_SESSION_ACTIVE, 'CSRF inicia una sesión cuando es necesaria');
public_session_test(strlen($token) === 64, 'CSRF genera un token de 32 bytes codificado en hexadecimal');

$_SESSION = [];
session_destroy();

echo "Pruebas de sesiones públicas superadas.\n";
