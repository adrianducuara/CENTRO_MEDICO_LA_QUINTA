<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/google_oauth.php';

// Exercise the clock allowance using signed local JWTs; no Google credentials or network.
$key = random_bytes(32);
$client = new class($key) extends Google\Client {
    public function __construct(private string $testKey) { parent::__construct(); }
    public function verifyIdToken($idToken = null) {
        return (array) Firebase\JWT\JWT::decode($idToken, new Firebase\JWT\Key($this->testKey, 'HS256'));
    }
};
$previousTimestamp = Firebase\JWT\JWT::$timestamp;
$previousLeeway = Firebase\JWT\JWT::$leeway;
Firebase\JWT\JWT::$timestamp = 1800000000;
Firebase\JWT\JWT::$leeway = 0;
$checks = 0;
try {
    foreach ([['iat' => 1800000002, 'exp' => 1800003600], ['iat' => 1800000031, 'exp' => 1800003600], ['iat' => 1799990000, 'exp' => 1799999969]] as $index => $claims) {
        $token = Firebase\JWT\JWT::encode($claims, $key, 'HS256');
        $accepted = false;
        try { $accepted = is_array(google_oauth_verify_id_token($client, $token)); }
        catch (Firebase\JWT\BeforeValidException | Firebase\JWT\ExpiredException) {}
        if ($accepted !== ($index === 0)) throw new RuntimeException('Incorrect clock bound');
        if (Firebase\JWT\JWT::$leeway !== 0) throw new RuntimeException('Leeway leaked');
        $checks += 2;
    }
    $invalid = Firebase\JWT\JWT::encode(['iat' => 1800000000, 'exp' => 1800003600], random_bytes(32), 'HS256');
    $rejected = false;
    try { google_oauth_verify_id_token($client, $invalid); }
    catch (Firebase\JWT\SignatureInvalidException) { $rejected = true; }
    if (!$rejected || Firebase\JWT\JWT::$leeway !== 0) throw new RuntimeException('Signature validation or restoration failed');
    $checks += 2;
    echo "PASS: $checks clock-skew checks.\n";
} finally {
    Firebase\JWT\JWT::$timestamp = $previousTimestamp;
    Firebase\JWT\JWT::$leeway = $previousLeeway;
}
