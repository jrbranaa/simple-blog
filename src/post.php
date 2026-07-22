<?php
require_once __DIR__ . '/includes/markdown.php';
$config = require __DIR__ . '/config.php';

$slug = preg_replace('/[^a-z0-9_-]/i', '', basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$post = $slug ? load_post($slug) : null;

if (!$post) {
    http_response_code(404);
    $page_title = '404 — Not Found';
    require __DIR__ . '/theme/header.php';
    echo '<p class="error">Post not found.</p>';
    require __DIR__ . '/theme/footer.php';
    exit;
}

$page_title = htmlspecialchars(str_replace(['"', "'"], '', $post['meta']['title'])) . ' — ' . htmlspecialchars($config['site_name']);
require __DIR__ . '/theme/header.php';
?>

<article class="post">
    <header class="post-header">
        <h1><?= htmlspecialchars($post['meta']['title']) ?></h1>
        <?php if ($post['meta']['date']): ?>
            <time datetime="<?= htmlspecialchars($post['meta']['date']) ?>">
                <?= htmlspecialchars(date('F j, Y', strtotime($post['meta']['date']))) ?>
            </time>
        <?php endif; ?>
        <?php if (!empty($post['tags'])): ?>
            <ul class="post-tags">
                <?php foreach ($post['tags'] as $tag): ?>
                    <li><?= htmlspecialchars($tag) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </header>
    <div class="post-content">
        <?= $post['html'] ?>
    </div>
    <footer class="post-footer">
        <a href="/">&larr; Back to posts</a>
    </footer>
</article>

<?php require __DIR__ . '/theme/footer.php'; ?>
