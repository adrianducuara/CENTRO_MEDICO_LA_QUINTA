<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: public, max-age=3600');

$siteUrl = seo_site_url();
if ($siteUrl === null) {
    http_response_code(503);
    echo '<?xml version="1.0" encoding="UTF-8"?><error>APP_URL no está configurada.</error>';
    exit;
}

/** @return string */
function sitemap_xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$urls = [
    ['path' => '', 'lastmod' => null],
    ['path' => 'nosotros.php', 'lastmod' => null],
    ['path' => 'servicios.php', 'lastmod' => null],
    ['path' => 'posts.php', 'lastmod' => null],
    ['path' => 'documents.php', 'lastmod' => null],
];

try {
    $posts = db()->query("SELECT id, updated_at FROM posts WHERE status = 'published' ORDER BY updated_at DESC, id DESC")->fetchAll();
    foreach ($posts as $post) {
        $urls[] = ['path' => 'post.php?id=' . (int) $post['id'], 'lastmod' => (string) $post['updated_at']];
    }
} catch (Throwable $exception) {
    error_log('No fue posible incluir publicaciones en sitemap.xml: ' . get_class($exception));
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    $location = $siteUrl . '/' . $url['path'];
    echo "  <url>\n    <loc>" . sitemap_xml_escape($location) . "</loc>\n";
    if (is_string($url['lastmod']) && $url['lastmod'] !== '') {
        $timestamp = strtotime($url['lastmod']);
        if ($timestamp !== false) {
            echo '    <lastmod>' . gmdate('Y-m-d', $timestamp) . "</lastmod>\n";
        }
    }
    echo "  </url>\n";
}
echo "</urlset>\n";
