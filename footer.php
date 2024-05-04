
<footer style="background-image: url('<?php echo BASE_URL; ?>images/banner.jpg'); background-repeat: no-repeat; background-size: cover; text-align: center; background-position: center -400px;">
<?php if (!isset($_SESSION['user_id'])): ?>
    <p>Not registered yet? <a href="register.php">Register here</a></p>
<?php endif; ?>
<p>&copy; <?php echo date("Y"); ?> Christian Community. All rights reserved.</p>
</footer>
</body>
</html>
