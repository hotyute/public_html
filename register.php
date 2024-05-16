<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'includes/database.php';  // Include the database connection

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $displayname = htmlspecialchars($_POST['displayname']); // Sanitize the displayname
    $role = "member";//htmlspecialchars($_POST['role']);

    // Check if the username already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $error_message = "Username already exists!";
    } else {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into the database including displayname
        $insert_stmt = $pdo->prepare("INSERT INTO users (username, displayname, password, rights) VALUES (?, ?, ?, ?)");
        if ($insert_stmt->execute([$username, $displayname, $password_hash, $role])) {
            $success_message = "User registered successfully!";
        } else {
            $error_message = "Failed to register user.";
        }
    }
}
?>

<?php include 'header.php'; ?>
<body>
    <h1>Register User</h1>
    <?php if (!empty($error_message)): ?>
        <p style="color: red;"><?= $error_message ?></p>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
        <p style="color: green;"><?= $success_message ?></p>
    <?php endif; ?>
    <form method="POST" action="register.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br>
        <label for="displayname">Full Name:</label>
        <input type="text" id="displayname" name="displayname" required><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>
        <!--<label for="role">Role:</label>
        <select id="role" name="role">
            <option value="member">Member</option>
            <option value="editor">Editor</option>
            <option value="admin">Admin</option>
        </select><br>-->
        <button type="submit">Register</button>
    </form>
</body>
<?php include 'footer.php'; ?>
