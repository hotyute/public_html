<?php
$footerLinks = [
    [
        'url' => '/archive.php',
        'text' => 'All Posts',
        'thumbnail' => ''
    ],
    [
        'url' => '#',
        'text' => 'Link 2',
        'thumbnail' => ''
    ],
    [
        'url' => '#',
        'text' => 'Link 3',
        'thumbnail' => ''
    ]
];
?>

<footer style="background-image: url('/images/footer.jpg');">
    <?php if (!isset($_SESSION['username'])) : ?>
        <p>Not registered yet? <a href="/register.php"><span class="registerl">Register here</span></a></p>
    <?php endif; ?>
    <p>&copy; <?php echo date("Y"); ?> DivineWord Community. All rights reserved.</p>

    <!-- Footer Links with Design -->
    <div class="footer-links">
        <ul>
            <?php foreach ($footerLinks as $link) : ?>
                <li>
                    <?php if ($link['thumbnail']) : ?>
                        <div class="footer-link-item">
                            <img src="<?php echo $link['thumbnail']; ?>" alt="<?php echo $link['text']; ?>" class="footer-thumbnail">
                            <a href="<?php echo $link['url']; ?>"><?php echo $link['text']; ?></a>
                        </div>
                    <?php else : ?>
                        <div class="footer-link-item">
                            <a href="<?php echo $link['url']; ?>"><?php echo $link['text']; ?></a>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</footer>
</body>

</html>

<?php if ($current_page == 'index') : ?>
    <script src="/js/post-preview.js"></script>
<?php endif; ?>
<?php if ($current_page == 'manage_users') : ?>
    <script src="/js/manage_users.js"></script>
<?php endif; ?>
<?php if ($current_page == 'members') : ?>
    <script src="/js/roster.js"></script>
<?php endif; ?>
<?php if ($current_page == 'post') : ?>
    <script src="/js/tools.js"></script>
<?php endif; ?>