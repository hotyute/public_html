<?php
$footerLinks = [
    ['url' => '/archive.php', 'text' => 'All Articles'],
    ['url' => '/members.php', 'text' => 'Members'],
    ['url' => '/contact.php', 'text' => 'Contact'],
    ['url' => '/about.php', 'text' => 'About'],
];
?>
<footer class="site-footer">
    <div class="site-footer__inner">
        <div class="site-footer__brand">
            <img src="/images/logo.png" alt="Divine Word" class="site-footer__logo">
            <p>Teachings, Articles, and Reflections<br>For the Little Flock.</p>
        </div>

        <nav class="footer-links" aria-label="Footer navigation">
            <ul>
                <?php foreach ($footerLinks as $link) : ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($link['text'], ENT_QUOTES, 'UTF-8'); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="site-footer__cta">
            <?php if (!isset($_SESSION['username'])) : ?>
                <p>Not registered yet? <a href="/register.php"><span class="registerl">Register here</span></a></p>
            <?php else : ?>
                <p>Signed in and ready to continue.</p>
                <a href="/userportal/user_portal.php">Open User Portal</a>
            <?php endif; ?>
        </div>

        <p class="site-footer__copy">&copy; <?php echo date("Y"); ?> DivineWord Community. All rights reserved.</p>
    </div>
</footer>

<?php if ($current_page == 'manage_users') : ?>
    <script src="/js/manage_users.js"></script>
<?php endif; ?>
<?php if ($current_page == 'members') : ?>
    <script src="/js/roster.js"></script>
<?php endif; ?>
<?php if ($current_page == 'post') : ?>
    <script src="/js/tools.js"></script>
    <script src="/js/article-reader.js"></script>
<?php endif; ?>
<?php if ($current_page == 'index') : ?>
    <script src="/js/article-carousel.js"></script>
    <script src="/js/featured-video-editor.js"></script>
    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin') : ?>
        <script src="/js/home-content-admin.js"></script>
    <?php endif; ?>
<?php endif; ?>
<?php if ($current_page == 'edit_video') : ?>
    <script src="/js/featured-video-editor.js"></script>
<?php endif; ?>
<?php if (in_array($current_page, ['index', 'post'], true) && isset($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'], true)) : ?>
    <script src="/js/inline-post-editor.js"></script>
<?php endif; ?>
</body>

</html>
