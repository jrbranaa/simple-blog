<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? $config['site_name']) ?></title>
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($config['site_name']) ?> RSS Feed" href="/feed.xml">
    <link rel="stylesheet" href="/theme/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/styles/github.min.css">
    <?php if (!empty($config['owa_base_url']) && !empty($config['owa_site_id'])): ?>
    <!-- Open Web Analytics -->
    <script type="text/javascript">
    //<![CDATA[
    var owa_baseUrl = '<?= htmlspecialchars($config['owa_base_url']) ?>';
    var owa_cmds = owa_cmds || [];
    owa_cmds.push(['setSiteId', '<?= htmlspecialchars($config['owa_site_id']) ?>']);
    owa_cmds.push(['trackPageView']);
    owa_cmds.push(['trackClicks']);
    (function() {
        var _owa = document.createElement('script'); _owa.type = 'text/javascript'; _owa.async = true;
        owa_baseUrl = ('https:' == document.location.protocol ? window.owa_baseSecUrl || owa_baseUrl.replace(/http:/, 'https:') : owa_baseUrl );
        _owa.src = owa_baseUrl + 'modules/base/dist/owa.tracker.js';
        var _owa_s = document.getElementsByTagName('script')[0]; _owa_s.parentNode.insertBefore(_owa, _owa_s);
    }());
    //]]>
    </script>
    <?php endif; ?>
</head>
<body>
<div class="page-container">
    <header class='header' id='masthead'>
        <div id='header-button-bar'>
            <div id='logo'>
                <a class='header-logo' href='/'><img src='/theme/logo.png' alt='<?= htmlspecialchars($config['site_name']) ?>'></a>
            </div>
        </div>
    </header>
    <div class="main-content">
        <div class="layout">
        <main>
