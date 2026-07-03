<?php

function parse_frontmatter(string $raw): array {
    $meta = ['title' => '', 'date' => '', 'description' => ''];
    $content = $raw;

    if (str_starts_with(ltrim($raw), '---')) {
        $raw = ltrim($raw);
        $end = strpos($raw, '---', 3);
        if ($end !== false) {
            $block = substr($raw, 3, $end - 3);
            $content = ltrim(substr($raw, $end + 3));
            foreach (explode("\n", $block) as $line) {
                if (str_contains($line, ':')) {
                    [$key, $val] = explode(':', $line, 2);
                    $meta[trim($key)] = trim($val);
                }
            }
        }
    }

    return ['meta' => $meta, 'content' => $content];
}

function markdown_to_html(string $text): string {
    $text = str_replace("\r\n", "\n", $text);

    // Extract fenced code blocks and inline code into placeholders so that
    // inline patterns (bold, italic, etc.) cannot corrupt their content.
    $placeholders = [];

    $text = preg_replace_callback('/```(\w*)\n(.*?)```/s', function ($m) use (&$placeholders) {
        $lang = $m[1] ? ' class="language-' . htmlspecialchars($m[1]) . '"' : '';
        $html = '<pre><code' . $lang . '>' . htmlspecialchars($m[2]) . '</code></pre>';
        $key  = "\x02CODE" . count($placeholders) . "\x02";
        $placeholders[$key] = $html;
        return $key;
    }, $text);

    $text = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$placeholders) {
        $html = '<code>' . htmlspecialchars($m[1]) . '</code>';
        $key  = "\x02CODE" . count($placeholders) . "\x02";
        $placeholders[$key] = $html;
        return $key;
    }, $text);

    // Blockquotes
    $text = preg_replace('/^> ?(.+)$/m', '<blockquote>$1</blockquote>', $text);

    // Headings
    $text = preg_replace('/^#{6} (.+)$/m', '<h6>$1</h6>', $text);
    $text = preg_replace('/^#{5} (.+)$/m', '<h5>$1</h5>', $text);
    $text = preg_replace('/^#{4} (.+)$/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/m',  '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/m',   '<h1>$1</h1>', $text);

    // Horizontal rule
    $text = preg_replace('/^(-{3,}|\*{3,}|_{3,})$/m', '<hr>', $text);

    // Unordered lists
    $text = preg_replace_callback('/(^[*\-] .+\n?)+/m', function ($m) {
        $items = preg_replace('/^[*\-] (.+)$/m', '<li>$1</li>', trim($m[0]));
        return '<ul>' . $items . '</ul>';
    }, $text);

    // Ordered lists
    $text = preg_replace_callback('/(^\d+\. .+\n?)+/m', function ($m) {
        $items = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', trim($m[0]));
        return '<ol>' . $items . '</ol>';
    }, $text);

    // Images (before links)
    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $text);

    // Links
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);

    // Bold and italic
    $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/',     '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/',          '<em>$1</em>', $text);
    $text = preg_replace('/___(.+?)___/',        '<strong><em>$1</em></strong>', $text);
    $text = preg_replace('/__(.+?)__/',          '<strong>$1</strong>', $text);
    $text = preg_replace('/_(.+?)_/',            '<em>$1</em>', $text);

    // Strikethrough
    $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);

    // Paragraphs: wrap blocks not already wrapped in a block tag
    $blocks = preg_split('/\n{2,}/', trim($text));
    $block_tags = ['<h1','<h2','<h3','<h4','<h5','<h6','<ul','<ol','<li','<blockquote','<pre','<hr','<img', "\x02CODE"];
    $out = [];
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;
        $is_block = false;
        foreach ($block_tags as $tag) {
            if (str_starts_with($block, $tag)) { $is_block = true; break; }
        }
        $out[] = $is_block ? $block : '<p>' . nl2br($block) . '</p>';
    }

    $text = implode("\n", $out);

    return str_replace(array_keys($placeholders), array_values($placeholders), $text);
}

function parse_tags(string $raw): array {
    $raw = trim($raw, " \t[]");
    if ($raw === '') return [];
    return array_map('trim', explode(',', $raw));
}

function load_post(string $slug): ?array {
    $path = __DIR__ . '/../posts/' . basename($slug) . '.md';
    if (!file_exists($path)) return null;
    $parsed = parse_frontmatter(file_get_contents($path));
    $parsed['slug'] = $slug;
    $parsed['html'] = markdown_to_html($parsed['content']);
    $parsed['tags'] = parse_tags($parsed['meta']['tags'] ?? '');
    return $parsed;
}

function load_page(string $slug): ?array {
    $path = __DIR__ . '/../pages/' . basename($slug) . '.md';
    if (!file_exists($path)) return null;
    $parsed = parse_frontmatter(file_get_contents($path));
    $parsed['slug'] = $slug;
    $parsed['html'] = markdown_to_html($parsed['content']);
    return $parsed;
}

function get_all_posts(): array {
    $today = date('Y-m-d');
    $posts = [];
    foreach (glob(__DIR__ . '/../posts/*.md') as $file) {
        $slug = basename($file, '.md');
        $parsed = parse_frontmatter(file_get_contents($file));
        $meta = $parsed['meta'];

        if (($meta['current state'] ?? '') !== 'published') continue;

        $publish_date = trim($meta['publish date'] ?? '');
        if ($publish_date === '') $publish_date = $today;
        if ($publish_date > $today) continue;

        $posts[] = array_merge($meta, ['slug' => $slug]);
    }
    usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
    return $posts;
}
