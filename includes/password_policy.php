<?php
declare(strict_types=1);

function password_policy_blocklist(): array
{
    static $list;
    return $list ??= json_decode(file_get_contents(__DIR__ . '/../config/password-blocklist.json'), true, 512, JSON_THROW_ON_ERROR);
}

function password_policy_terms(array $user): array
{
    $terms = [(string) ($user['username'] ?? ''), explode('@', (string) ($user['email'] ?? ''))[0]];
    foreach (['full_name', 'name'] as $field) {
        $name = (string) ($user[$field] ?? '');
        $terms[] = $name;
        foreach (preg_split('/[\s\p{P}]+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            if (preg_match_all('/./us', $part) >= 3) $terms[] = $part;
        }
    }
    return array_values(array_unique(array_filter($terms, static fn ($value) => $value !== '')));
}

function security_password_error(string $password, string $confirmation, array $user = []): ?string
{
    if (!preg_match('//u', $password) || !preg_match('//u', $confirmation)) return 'La contraseña debe contener texto UTF-8 válido.';
    $length = preg_match_all('/./us', $password);
    if ($length < 8 || $length > 16) return 'Usa entre 8 y 16 caracteres.';
    foreach (password_policy_blocklist() as $blocked) {
        if (preg_match('/\A' . preg_quote($blocked, '/') . '\z/iu', $password)) return 'Esta contraseña es demasiado común. Elige una frase menos predecible.';
    }
    // Reject runs and passwords made entirely from a short repeated unit.
    if (preg_match('/(.)\1{5,}/us', $password) || preg_match('/\A(.{1,4})\1{2,}\z/uis', $password)) return 'Evita caracteres o grupos cortos repetidos, como aaaaaa o abcabcabc.';
    foreach (['0123456789', 'abcdefghijklmnopqrstuvwxyz', 'qwertyuiop', 'asdfghjkl', 'zxcvbnm'] as $sequence) {
        foreach ([$sequence, strrev($sequence)] as $direction) {
            for ($i = 0; $i <= strlen($direction) - 4; $i++) {
                if (stripos($password, substr($direction, $i, 4)) !== false) return 'Evita secuencias predecibles, como 1234, abcd o qwerty.';
            }
        }
    }
    foreach (password_policy_terms($user) as $term) {
        if (preg_match('/' . preg_quote($term, '/') . '/iu', $password)) return 'No incluyas tu usuario, la parte anterior a @ de tu correo ni tu nombre.';
    }
    if ($password !== $confirmation) return 'La nueva contraseña y la confirmación no coinciden.';
    return null;
}

function security_password_hash(string $password): string
{
    if (!defined('PASSWORD_ARGON2ID')) throw new RuntimeException('El servidor necesita soporte Argon2id para guardar contraseñas.');
    return password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1]);
}

/** Local-only feedback data. Passwords are never transmitted for live validation. */
function password_policy_feedback(array $user = []): void
{
    $config = ['blocklist' => password_policy_blocklist(), 'terms' => password_policy_terms($user)];
    echo '<p id="password-help">Usa entre 8 y 16 caracteres. Puedes incluir espacios, símbolos y letras sin mezclar obligatoriamente tipos de caracteres.</p>';
    echo '<p id="password-feedback" role="status" aria-live="polite"></p><p id="confirmation-feedback" role="status" aria-live="polite"></p>';
    echo '<script type="application/json" data-password-policy-config>' . json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) . '</script>';
    echo '<script src="../public/assets/js/password-policy.js" defer></script>';
}
