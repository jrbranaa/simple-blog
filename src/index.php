<?php
require_once __DIR__ . '/includes/markdown.php';
$config = require __DIR__ . '/config.php';

$page_title  = htmlspecialchars($config['site_name']);
$all_posts   = get_all_posts();

$per_page    = $config['posts_per_page'] ?? 5;
$total       = count($all_posts);
$total_pages = max(1, (int) ceil($total / $per_page));
$current     = max(1, min($total_pages, (int) ($_GET['page'] ?? 1)));
$posts       = array_slice($all_posts, ($current - 1) * $per_page, $per_page);

require __DIR__ . '/theme/header.php';
?>

<section class="post-list">
    <h1>Posts</h1>
    <?php if (empty($all_posts)): ?>
        <p>No posts yet.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
        <article class="post-summary">
            <a href="/posts/<?= htmlspecialchars($post['slug']) ?>" class="post-summary-link">
                <h2><?= htmlspecialchars($post['title']) ?></h2>
                <?php if ($post['date']): ?>
                    <time datetime="<?= htmlspecialchars($post['date']) ?>"><?= htmlspecialchars(date('F j, Y', strtotime($post['date']))) ?></time>
                <?php endif; ?>
                <?php if ($post['description']): ?>
                    <p><?= htmlspecialchars($post['description']) ?></p>
                <?php endif; ?>
            </a>
        </article>
        <?php endforeach; ?>

        <?php if ($total_pages > 1): ?>
        <nav class="pagination" aria-label="Post pages">
            <?php if ($current > 1): ?>
                <a href="/?page=<?= $current - 1 ?>" class="pagination-btn">&larr; Newer</a>
            <?php else: ?>
                <span class="pagination-btn pagination-btn--disabled">&larr; Newer</span>
            <?php endif; ?>

            <span class="pagination-info"><?= $current ?> / <?= $total_pages ?></span>

            <?php if ($current < $total_pages): ?>
                <a href="/?page=<?= $current + 1 ?>" class="pagination-btn">Older &rarr;</a>
            <?php else: ?>
                <span class="pagination-btn pagination-btn--disabled">Older &rarr;</span>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/theme/footer.php'; ?>
