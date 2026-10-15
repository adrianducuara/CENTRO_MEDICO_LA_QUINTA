<?php
declare(strict_types=1);

// Copy this file as runtime.local.php on the production server. It is not
// committed to Git. Use the canonical HTTPS address without a trailing slash.
return [
    'app_url' => 'https://example.com',
    'gmail_oauth_redirect_uri' => 'https://example.com/admin/google-oauth-callback.php',
];
