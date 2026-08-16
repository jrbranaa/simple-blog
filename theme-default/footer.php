        </main>
        <?php require __DIR__ . '/sidebar.php'; ?>
        </div><!-- layout -->
    </div><!-- main-content -->
    <footer id="footer">
        <nav class="footer-nav">
            <a href="/pages/about">About</a>
            <a href="/feed.xml" title="Subscribe via RSS"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;" aria-hidden="true"><path d="M4 11a9 9 0 0 1 9 9"></path><path d="M4 4a16 16 0 0 1 16 16"></path><circle cx="5" cy="19" r="1"></circle></svg>RSS</a>
        </nav>
        <div class="copyright-container">
            <small><?= htmlspecialchars($config['site_name']) ?> &copy; <?php echo date("Y"); ?></small>
            <?php
            $vf = __DIR__ . '/../VERSION';
            if (is_file($vf)): ?>
            <small>Powered by Simple-Blog v<?= htmlspecialchars(trim(file_get_contents($vf))) ?></small>
            <?php endif; ?>
        </div>
    </footer>
</div><!-- page-container -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/highlight.min.js"></script>
<script>hljs.highlightAll();</script>
</body>
</html>
