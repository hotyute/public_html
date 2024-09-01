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
        $insert_stmt = $pdo->prepare("INSERT INTO users (username, displayname, password, role) VALUES (?, ?, ?, ?)");
        if ($insert_stmt->execute([$username, $displayname, $password_hash, $role])) {
            $success_message = "User registered successfully!";
        } else {
            $error_message = "Failed to register user.";
        }
    }
}
?>
<?php include 'header.php'; ?>

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
    <button type="submit">Register</button>
</form>

<?php include 'footer.php'; ?>

<!-- Include the JavaScript code -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const displaynameInput = document.getElementById('displayname');
    const submitButton = document.querySelector('button');

    // Disable submit button initially
    submitButton.disabled = true;

    // Enable submit button only when all fields are filled
    function checkFormValidity() {
        if (usernameInput.value && passwordInput.value && displaynameInput.value) {
            submitButton.disabled = false;
        } else {
            submitButton.disabled = true;
        }
    }

    // Check form validity on input
    usernameInput.addEventListener('input', checkFormValidity);
    passwordInput.addEventListener('input', checkFormValidity);
    displaynameInput.addEventListener('input', checkFormValidity);

    // Password strength checker
    passwordInput.addEventListener('input', function() {
        const strengthIndicator = document.createElement('span');
        strengthIndicator.style.display = 'block';
        strengthIndicator.style.marginTop = '10px';

        const strength = calculatePasswordStrength(passwordInput.value);

        switch (strength) {
            case 'weak':
                strengthIndicator.textContent = 'Password Strength: Weak';
                strengthIndicator.style.color = '#e74c3c';
                break;
            case 'medium':
                strengthIndicator.textContent = 'Password Strength: Medium';
                strengthIndicator.style.color = '#f39c12';
                break;
            case 'strong':
                strengthIndicator.textContent = 'Password Strength: Strong';
                strengthIndicator.style.color = '#2ecc71';
                break;
            default:
                strengthIndicator.textContent = '';
        }

        if (passwordInput.nextElementSibling) {
            passwordInput.nextElementSibling.remove();
        }
        passwordInput.insertAdjacentElement('afterend', strengthIndicator);
    });

    function calculatePasswordStrength(password) {
        if (password.length < 6) {
            return 'weak';
        }
        if (password.length >= 6 && password.length < 10) {
            return 'medium';
        }
        if (password.length >= 10) {
            return 'strong';
        }
    }
});
</script>
