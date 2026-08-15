<?php
require_once __DIR__ . '/includes/markdown.php';
$config = require __DIR__ . '/config.php';

function excerpt_from_html(string $html, int $length = 300): string {
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

function rss_escape(string $text): string {
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$forwarded_proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$is_https        = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || strtolower(explode(',', $forwarded_proto)[0]) === 'https';
$scheme   = $is_https ? 'https' : 'http';
$base_url = $scheme . '://' . $_SERVER['HTTP_HOST'];

$all_posts = get_all_posts();
$posts     = array_slice($all_posts, 0, $config['feed_items'] ?? 20);

header('Content-Type: application/rss+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
<channel>
    <title><?= rss_escape($config['site_name']) ?></title>
    <link><?= rss_escape($base_url . '/') ?></link>
    <description><?= rss_escape($config['site_name']) ?> — latest posts</description>
    <language><?= rss_escape($config['site_language'] ?? 'en-us') ?></language>
    <?php foreach ($posts as $post): ?>
        <?php
        $excerpt = trim($post['description'] ?? '') !== ''
            ? $post['description']
            : excerpt_from_html(load_post($post['slug'])['html'] ?? '');
        $link     = $base_url . '/posts/' . rawurlencode($post['slug']);
        $pub_date = $post['date'] ? date(DATE_RSS, strtotime($post['date'])) : date(DATE_RSS);
        ?>
    <item>
        <title><?= rss_escape($post['title']) ?></title>
        <link><?= rss_escape($link) ?></link>
        <guid isPermaLink="true"><?= rss_escape($link) ?></guid>
        <pubDate><?= rss_escape($pub_date) ?></pubDate>
        <description><?= rss_escape($excerpt) ?></description>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
