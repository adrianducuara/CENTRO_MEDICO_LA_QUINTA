<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

function google_oauth_key(): string {
    $raw = trim((string) getenv('APP_ENCRYPTION_KEY'));
    if ($raw !== '') { $decoded = base64_decode($raw, true); return $decoded !== false && strlen($decoded) === 32 ? $decoded : hash('sha256', $raw, true); }
    $path = APP_SECRET_STORAGE . DIRECTORY_SEPARATOR . 'oauth-settings.key';
    if (!is_dir(APP_SECRET_STORAGE) && !mkdir(APP_SECRET_STORAGE, 0700, true) && !is_dir(APP_SECRET_STORAGE)) throw new RuntimeException('No se pudo preparar el almacenamiento seguro.');
    if (!is_file($path)) { $key = random_bytes(32); if (file_put_contents($path, $key, LOCK_EX) === false) throw new RuntimeException('No se pudo crear la clave de cifrado.'); @chmod($path, 0600); return $key; }
    $key = file_get_contents($path); if ($key === false || strlen($key) !== 32) throw new RuntimeException('La clave de cifrado no es válida.'); return $key;
}
function google_oauth_encrypt(string $value): string { if (!extension_loaded('openssl')) throw new RuntimeException('OpenSSL es obligatoria.'); $iv=random_bytes(12); $tag=''; $data=openssl_encrypt($value,'aes-256-gcm',google_oauth_key(),OPENSSL_RAW_DATA,$iv,$tag); if ($data===false) throw new RuntimeException('No fue posible cifrar el dato.'); return base64_encode($iv.$tag.$data); }
function google_oauth_decrypt(?string $value): ?string { if ($value===null || $value==='' || !extension_loaded('openssl')) return null; $raw=base64_decode($value,true); if ($raw===false || strlen($raw)<29) return null; $result=openssl_decrypt(substr($raw,28),'aes-256-gcm',google_oauth_key(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16)); return $result===false?null:$result; }
/** @return array<string,mixed>|null */
function google_oauth_settings(): ?array { try { $row=db()->query('SELECT * FROM google_oauth_tokens WHERE id=1')->fetch(); if ($row===false) return null; foreach(['client_secret','access_token','refresh_token'] as $name) $row[$name]=google_oauth_decrypt($row[$name.'_encrypted']??null); return $row; } catch(PDOException) { return null; } }
function google_oauth_redirect_uri(): string { $env=trim((string)getenv('GMAIL_OAUTH_REDIRECT_URI')); if($env!=='') return google_oauth_validate_redirect_uri($env); $host=(string)($_SERVER['HTTP_HOST']??'127.0.0.1:8083'); if(preg_match('/\A(?:localhost|127\.0\.0\.1)(?::\d+)?\z/i',$host)!==1) throw new RuntimeException('Define GMAIL_OAUTH_REDIRECT_URI con la URL HTTPS pública.'); return google_oauth_validate_redirect_uri('http://'.$host.'/admin/google-oauth-callback.php'); }
function google_oauth_validate_redirect_uri(string $uri): string { $p=parse_url($uri); $host=strtolower((string)($p['host']??'')); $scheme=strtolower((string)($p['scheme']??'')); $local=in_array($host,['localhost','127.0.0.1'],true); if(($local?!in_array($scheme,['http','https'],true):$scheme!=='https') || ($p['path']??'')!=='/admin/google-oauth-callback.php' || isset($p['user'],$p['pass'])) throw new RuntimeException('La URI de redirección OAuth no es válida.'); return $uri; }
/** @param array<string,mixed> $settings */
function google_oauth_client(array $settings): Google\Client { $c=new Google\Client(); $c->setApplicationName('Centro Médico La Quinta'); $c->setClientId((string)$settings['client_id']); $c->setClientSecret((string)$settings['client_secret']); $c->setRedirectUri(google_oauth_redirect_uri()); $c->setScopes(['openid','email',Google\Service\Gmail::GMAIL_SEND]); $c->setAccessType('offline'); $c->setPrompt('consent select_account'); return $c; }
function google_oauth_pkce_verifier(): string { return rtrim(strtr(base64_encode(random_bytes(64)),'+/','-_'),'='); }
function google_oauth_pkce_challenge(string $verifier): string { return rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'='); }
/** @param array<string,mixed> $settings */
function google_oauth_authorization_url(array $settings,string $state,string $verifier): string { $c=google_oauth_client($settings); $c->setState($state); return $c->createAuthUrl().'&'.http_build_query(['code_challenge'=>google_oauth_pkce_challenge($verifier),'code_challenge_method'=>'S256'],'','&',PHP_QUERY_RFC3986); }
function google_oauth_save_settings(string $clientId, string $clientSecret, string $sender, string $recipient): void
{
    $cur = google_oauth_settings();
    if ($cur !== null && $cur['client_id'] !== $clientId && $clientSecret === '') {
        throw new InvalidArgumentException('Ingresa el Client Secret al cambiar el Client ID.');
    }
    $secret = $clientSecret !== '' ? google_oauth_encrypt($clientSecret) : ($cur['client_secret_encrypted'] ?? null);
    if ($secret === null) throw new InvalidArgumentException('Ingresa el Client Secret.');
    $changed = $cur !== null && ($cur['client_id'] !== $clientId || $clientSecret !== '' || strcasecmp($cur['sender_email'], $sender) !== 0);
    // Native PDO prepares require a separate placeholder for each occurrence.
    $sql = "INSERT INTO google_oauth_tokens
        (id, client_id, client_secret_encrypted, sender_email, recipient_email, connection_status,
         access_token_encrypted, refresh_token_encrypted, token_expires_at)
        VALUES (1, :client, :secret, :sender, :recipient, 'pending', NULL, NULL, NULL)
        ON DUPLICATE KEY UPDATE
        client_id = VALUES(client_id), client_secret_encrypted = VALUES(client_secret_encrypted),
        sender_email = VALUES(sender_email), recipient_email = VALUES(recipient_email),
        connection_status = IF(:reset_status, 'pending', connection_status),
        access_token_encrypted = IF(:reset_access, NULL, access_token_encrypted),
        refresh_token_encrypted = IF(:reset_refresh, NULL, refresh_token_encrypted),
        token_expires_at = IF(:reset_expiry, NULL, token_expires_at),
        authorized_email = IF(:reset_email, NULL, authorized_email),
        last_verified_at = IF(:reset_verified, NULL, last_verified_at), last_error = NULL";
    db()->prepare($sql)->execute([
        'client' => $clientId, 'secret' => $secret, 'sender' => $sender, 'recipient' => $recipient,
        'reset_status' => (int) $changed, 'reset_access' => (int) $changed,
        'reset_refresh' => (int) $changed, 'reset_expiry' => (int) $changed,
        'reset_email' => (int) $changed, 'reset_verified' => (int) $changed,
    ]);
}
/** @param array<string,mixed> $settings @return array<string,mixed> */
function google_oauth_exchange_code(array $settings,string $code,string $verifier): array { $token=google_oauth_client($settings)->fetchAccessTokenWithAuthCode($code,$verifier); if(!is_array($token)||isset($token['error'])||!is_string($token['access_token']??null)) throw new RuntimeException('Google no entregó un token válido.'); return $token; }
/** @param array<string,mixed> $settings @param array<string,mixed> $token */
function google_oauth_verified_email(array $settings,array $token): string { $id=$token['id_token']??null; $claims=is_string($id)?google_oauth_verify_id_token(google_oauth_client($settings), $id):false; $email=is_array($claims)?($claims['email']??null):null; if(!is_string($email)||!filter_var($email,FILTER_VALIDATE_EMAIL)||empty($claims['email_verified'])) throw new RuntimeException('No se pudo verificar el correo autorizado.'); return strtolower($email); }

/** Allow small clock differences only during Google's full ID-token validation. */
function google_oauth_verify_id_token(Google\Client $client, string $idToken): array|false
{
    $previousLeeway = Firebase\JWT\JWT::$leeway;
    Firebase\JWT\JWT::$leeway = 30;
    try {
        return $client->verifyIdToken($idToken);
    } finally {
        Firebase\JWT\JWT::$leeway = $previousLeeway;
    }
}
/** @param array<string,mixed> $tokens */
function google_oauth_save_tokens(array $tokens,string $email): void { $cur=google_oauth_settings(); $refresh=$tokens['refresh_token']??($cur['refresh_token']??null); if(!is_string($refresh)||$refresh==='') throw new RuntimeException('Google no entregó refresh token. Desconecta y vuelve a autorizar.'); $ok=strtolower((string)($cur['sender_email']??''))===$email; $status=$ok?'authorized':'error'; $error=$ok?null:'El remitente configurado no coincide con la cuenta autorizada.'; $expiry=date('Y-m-d H:i:s',time()+max(60,(int)($tokens['expires_in']??3600))-60); db()->prepare('UPDATE google_oauth_tokens SET access_token_encrypted=:access,refresh_token_encrypted=:refresh,token_expires_at=:expiry,authorized_email=:email,connection_status=:status,last_error=:error,last_checked_at=NOW() WHERE id=1')->execute(['access'=>google_oauth_encrypt((string)$tokens['access_token']),'refresh'=>google_oauth_encrypt($refresh),'expiry'=>$expiry,'email'=>$email,'status'=>$status,'error'=>$error]); if(!$ok) throw new RuntimeException('Autoriza la misma cuenta configurada como remitente.'); }
function google_oauth_mark_status(string $status,?string $error=null,bool $checked=false): void { db()->prepare('UPDATE google_oauth_tokens SET connection_status=:status,last_error=:error,last_checked_at=IF(:checked,NOW(),last_checked_at) WHERE id=1')->execute(['status'=>$status,'error'=>$error,'checked'=>$checked?1:0]); }
function google_oauth_connection_state(): string { $s=google_oauth_settings(); if($s===null||empty($s['client_id'])||empty($s['client_secret'])) return 'unconfigured'; if(empty($s['refresh_token'])) return 'pending'; return (string)($s['connection_status']??'authorized'); }
function google_oauth_is_connected(): bool { return google_oauth_connection_state()==='verified'; }
/** @return array<string,mixed>|null */
function google_oauth_access_token_data(): ?array { $s=google_oauth_settings(); if($s===null||!is_string($s['refresh_token']??null)) return null; if(is_string($s['access_token']??null)&&is_string($s['token_expires_at']??null)&&strtotime($s['token_expires_at'])>time()) return ['access_token'=>$s['access_token']]; try { $c=google_oauth_client($s); $c->fetchAccessTokenWithRefreshToken($s['refresh_token']); $t=$c->getAccessToken(); if(!is_array($t)||!is_string($t['access_token']??null)) throw new RuntimeException('No se pudo renovar el token.'); google_oauth_save_tokens($t,(string)$s['authorized_email']); return $t; } catch(Throwable $e) { google_oauth_mark_status('reauth_required','La autorización expiró o fue revocada.',true); error_log('Renovación OAuth falló: '.$e->getMessage()); return null; } }
function google_oauth_access_token(): ?string { $t=google_oauth_access_token_data(); return is_string($t['access_token']??null)?$t['access_token']:null; }
function google_oauth_disconnect(): void { db()->exec("UPDATE google_oauth_tokens SET access_token_encrypted=NULL,refresh_token_encrypted=NULL,token_expires_at=NULL,authorized_email=NULL,connection_status='pending',last_error=NULL,last_checked_at=NULL,last_verified_at=NULL WHERE id=1"); }
