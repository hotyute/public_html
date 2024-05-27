<footer style="background-image: url('<?php echo BASE_URL; ?>images/banner.jpg'); background-repeat: no-repeat; background-size: cover; text-align: center; background-position: center -400px;">
    <?php if (!isset($_SESSION['username'])) : ?>
        <p>Not registered yet? <a href="register.php">Register here</a></p>
    <?php endif; ?>
    <p>&copy; <?php echo date("Y"); ?> DivineWord Community. All rights reserved.</p>
</footer>
</body>

</html>

<?php if ($current_page == 'index') : ?>
    <script src="<?php echo BASE_URL; ?>js/post-preview.js"></script>
<?php endif; ?>
<?php if ($current_page == 'manage_users') : ?>
    <script src="<?php echo BASE_URL; ?>js/manage_users.js"></script>
<?php endif; ?>