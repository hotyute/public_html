<?php include 'header.php'; ?>
<div style="display: flex;">
    <main style="flex: 3; padding: 20px;">
        <section>
            <h2>Welcome to Our Community</h2>
            <p>This is the home of our Christian community where we share insights, teachings, and fellowship together.</p>
        </section>
    </main>
    <aside style="flex: 1; background-color: #f0f0f0; padding: 20px;">
        <h3>Sidebar Content</h3>
        <ul>
            <li><a href="#">Link 1</a></li>
            <li><a href="#">Link 2</a></li>
            <li><a href="#">Link 3</a></li>
        </ul>
    </aside>
</div>
<?php include 'footer.php'; ?>


<?php if (!isset($_SESSION['user_id'])): ?>
    <p>Not registered yet? <a href="register.php">Register here</a></p>
<?php endif; ?>

<!-- Link to registration page -->
<p>If you are not registered, please <a href='register.php'>register here</a>.</p>
