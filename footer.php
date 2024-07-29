<?php
$footerLinks = [
    [
        'url' => BASE_URL . 'archive.php',
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

<footer class="footer">
    <?php if (!isset($_SESSION['username'])) : ?>
        <p>Not registered yet? <a href="register.php">Register here</a></p>
    <?php endif; ?>
    <p>&copy; <?php echo date("Y"); ?> DivineWord Community. All rights reserved.</p>

    <!-- Footer Links with Design -->
    <div class="footer-links">
        <ul>
            <?php foreach ($footerLinks as $link) : ?>
                <li>
                    <div class="footer-link-item">
                        <?php if ($link['thumbnail']) : ?>
                            <img src="<?php echo $link['thumbnail']; ?>" alt="<?php echo $link['text']; ?>" class="footer-thumbnail">
                        <?php endif; ?>
                        <a href="<?php echo $link['url']; ?>"><?php echo $link['text']; ?></a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Social Media Icons (example) -->
    <div class="social-icons">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
    </div>
</footer>
</body>
</html>

<?php if ($current_page == 'index') : ?>
    <script src="<?php echo BASE_URL; ?>js/post-preview.js"></script>
<?php endif; ?>
<?php if ($current_page == 'manage_users') : ?>
    <script src="<?php echo BASE_URL; ?>js/manage_users.js"></script>
<?php endif; ?>
<?php if ($current_page == 'roster') : ?>
    <script src="<?php echo BASE_URL; ?>js/roster.js"></script>
<?php endif; ?>
