<?php
$footerLinks = [
    ['url' => '/archive.php', 'text' => 'All Posts', 'thumbnail' => ''],
    ['url' => '#', 'text' => 'Link 2', 'thumbnail' => ''],
    ['url' => '#', 'text' => 'Link 3', 'thumbnail' => ''],
];
?>
<footer style="background-image: url('/images/footer.jpg');">
    <?php if (!isset($_SESSION['username'])) : ?>
        <p>Not registered yet? <a href="/register.php"><span class="registerl">Register here</span></a></p>
    <?php endif; ?>
    <p>&copy; <?php echo date("Y"); ?> DivineWord Community. All rights reserved.</p>

    <div class="footer-links">
        <ul>
            <?php foreach ($footerLinks as $link) : ?>
                <li>
                    <div class="footer-link-item">
                        <?php if (!empty($link['thumbnail'])) : ?>
                            <img src="<?php echo $link['thumbnail']; ?>" alt="<?php echo $link['text']; ?>" class="footer-thumbnail">
                        <?php endif; ?>
                        <a href="<?php echo $link['url']; ?>"><?php echo $link['text']; ?></a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</footer>

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
</body>

</html>