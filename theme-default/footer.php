        </main>
        <?php require __DIR__ . '/sidebar.php'; ?>
        </div><!-- layout -->
    </div><!-- main-content -->
    <footer id="footer">
        <nav class="footer-nav">
            <a href="/pages/about">About</a>
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
