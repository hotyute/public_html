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
            $_SESSION['user_id'] = $user['user_id'];
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
<div style="display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f9f9f9;">
    <form method="POST" action="login.php" style="padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background: white; border-radius: 8px; width: 300px;">
        <h2 style="text-align: center;">Login</h2>
        <?php if ($error_message != ''): ?>
            <p style="color: red; text-align: center;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <div style="margin-bottom: 20px;">
            <label for="username" style="display: block; margin-bottom: 5px;">Username:</label>
            <input type="text" id="username" name="username" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <div style="margin-bottom: 20px;">
            <label for="password" style="display: block; margin-bottom: 5px;">Password:</label>
            <input type="password" id="password" name="password" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <div style="text-align: center;">
            <button type="submit" style="background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Login</button>
        </div>
    </form>
</div>
<?php include 'footer.php'; ?>
