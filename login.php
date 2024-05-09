<?php
include 'includes/config.php';
include 'includes/database.php';
session_start();

$error_message = ''; // Initialize the error message variable

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Incorrect password!";
        }
    } else {
        $error_message = "User does not exist!";
    }
}
include 'header.php';
?>
<div class="login-form">
    <form method="POST" action="login.php">
        <h2 style="text-align: center;">Login</h2>
        <?php if ($error_message != ''): ?>
            <p style="color: red; text-align: center;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <div style="margin-bottom: 20px;">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div style="margin-bottom: 20px;">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div style="text-align: center;">
            <button type="submit">Login</button>
        </div>
    </form>
</div>
<?php include 'footer.php'; ?>
