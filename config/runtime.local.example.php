<?php
declare(strict_types=1);

// Copy this file as runtime.local.php on the production server. It is not
// committed to Git. Use the canonical HTTPS address without a trailing slash.
// This value powers canonical tags, robots.txt and sitemap.xml; do not leave
// example.com in the production copy.
return [
    'app_url' => 'https://example.com',
    'gmail_oauth_redirect_uri' => 'https://example.com/admin/google-oauth-callback.php',
];
