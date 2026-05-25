<?php
$footerColumns = [
    [
        'heading' => 'Explore',
        'links' => [
            ['url' => '/archive.php', 'text' => 'All Articles'],
            ['url' => '/members.php', 'text' => 'Members'],
            ['url' => '/about.php', 'text' => 'About'],
        ],
    ],
    [
        'heading' => 'Community',
        'links' => [
            ['url' => '/contact.php', 'text' => 'Contact Us'],
            ['url' => '/search.php', 'text' => 'Search'],
            ['url' => '/notifications.php', 'text' => 'Notifications'],
        ],
    ],
    [
        'heading' => 'Account',
        'links' => isset($_SESSION['username'])
            ? [
                ['url' => '/userportal/user_portal.php', 'text' => 'User Portal'],
                ['url' => '/userportal/messages.php', 'text' => 'Messages'],
                ['url' => '/userportal/user_settings.php', 'text' => 'Settings'],
            ]
            : [
                ['url' => '/login.php', 'text' => 'Login'],
                ['url' => '/register.php', 'text' => 'Register'],
            ],
    ],
];
?>
<footer class="site-footer">
    <div class="site-footer__main">
        <div class="site-footer__brand">
            <img src="/images/logo.png" alt="Divine Word" class="site-footer__logo">
            <p>Teachings, Articles, and Reflections<br>For the Little Flock.</p>
        </div>

        <div class="site-footer__columns" aria-label="Footer navigation">
            <?php foreach ($footerColumns as $column) : ?>
                <section class="site-footer__column">
                    <h2><?php echo htmlspecialchars($column['heading'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <ul>
                        <?php foreach ($column['links'] as $link) : ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($link['text'], ENT_QUOTES, 'UTF-8'); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="site-footer__bottom">
        <p>&copy; <?php echo date("Y"); ?> DivineWord Community. All rights reserved.</p>
        <?php if (!isset($_SESSION['username'])) : ?>
            <p>Not registered yet? <a href="/register.php">Register here</a></p>
        <?php endif; ?>
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
    <script src="/js/post-navigation.js"></script>
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
