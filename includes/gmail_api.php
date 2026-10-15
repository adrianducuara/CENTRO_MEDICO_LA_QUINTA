<?php
declare(strict_types=1);

function gmail_api_is_configured(): bool { require_once __DIR__ . '/google_oauth.php'; return google_oauth_connection_state() !== 'unconfigured'; }
function gmail_api_base64url(string $value): string { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
/** @return array{ok:bool,error:?string} */
function gmail_api_send_message(string $subject, string $html, ?string $replyTo = null, ?string $recipientOverride = null): array {
    require_once __DIR__ . '/google_oauth.php';
    $settings = google_oauth_settings(); $token = google_oauth_access_token();
    if ($settings === null || $token === null) return ['ok'=>false,'error'=>'La autorización de Google no está disponible.'];
    $sender = strtolower((string) ($settings['sender_email'] ?? '')); $authorized = strtolower((string) ($settings['authorized_email'] ?? '')); $recipient = $recipientOverride ?? (string) ($settings['recipient_email'] ?? '');
    if ($sender === '' || $sender !== $authorized || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) return ['ok'=>false,'error'=>'El remitente configurado no coincide con la cuenta autorizada.'];
    $headers = ['From: Centro Médico La Quinta <'.$sender.'>', 'To: '.$recipient];
    if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $headers[] = 'Reply-To: '.$replyTo;
    $headers[] = 'Subject: =?UTF-8?B?'.base64_encode(preg_replace('/[\r\n]+/', ' ', $subject) ?? 'Notificación').'?=';
    $headers[] = 'MIME-Version: 1.0'; $headers[] = 'Content-Type: text/html; charset=UTF-8'; $headers[] = 'Content-Transfer-Encoding: base64'; $headers[] = ''; $headers[] = chunk_split(base64_encode($html), 76, "\r\n");
    try {
        $client = google_oauth_client($settings); $client->setAccessToken(['access_token'=>$token]);
        $gmail = new Google\Service\Gmail($client); $message = new Google\Service\Gmail\Message(); $message->setRaw(gmail_api_base64url(implode("\r\n", $headers)));
        $gmail->users_messages->send('me', $message);
        google_oauth_mark_status('verified', null, true); db()->exec('UPDATE google_oauth_tokens SET last_verified_at=NOW() WHERE id=1');
        return ['ok'=>true,'error'=>null];
    } catch (Throwable $e) { google_oauth_mark_status('error', 'Gmail API rechazó la verificación. Revisa permisos y configuración.', true); error_log('Gmail API falló: '.$e->getMessage()); return ['ok'=>false,'error'=>'Gmail API no pudo enviar el correo.']; }
}
function gmail_api_send_test_email(): bool { $result = gmail_api_send_message('Prueba de conexión Gmail - Centro Médico La Quinta', '<div style="font-family:Arial,sans-serif;color:#172c43"><h2>Conexión Gmail verificada</h2><p>La integración OAuth puede enviar notificaciones de PQRSF.</p></div>'); return $result['ok']; }
/** @param array<string,string> $values */
function send_pqrsf_gmail_api_notification(string $reference, array $values): bool { require_once __DIR__ . '/mailer.php'; $message = pqrsf_notification_message($reference, $values); return gmail_api_send_message($message['subject'], $message['html'], $values['email'])['ok']; }
