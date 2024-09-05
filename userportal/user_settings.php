<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

require_once '../base_config.php';  // Include the database connection

// Check if the request is for email validation via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_email'])) {
    $email = htmlspecialchars($_POST['email']);
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit();
}

// Handle form submission for updating email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    $new_email = htmlspecialchars($_POST['email']);
    // TODO: Add further validation for the email

    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
    if ($stmt->execute([$new_email, $_SESSION['user_id']])) {
        $success_message = "Email updated successfully!";
    } else {
        $error_message = "Failed to update email.";
    }
}

// Handle form submission for updating password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    // TODO: Add further validation for the password

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($stmt->execute([$new_password, $_SESSION['user_id']])) {
        $success_message = "Password updated successfully!";
    } else {
        $error_message = "Failed to update password.";
    }
}

// Include necessary files and the header
include 'header.php';
?>

<div class="settings-container">
    <h1>User Settings</h1>

    <?php if (!empty($error_message)): ?>
        <p style="color: red;"><?= $error_message ?></p>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
        <p style="color: green;"><?= $success_message ?></p>
    <?php endif; ?>

    <!-- Form to update email -->
    <form id="email-form" method="POST" action="user_settings.php">
        <div class="form-group">
            <label for="email">Update Email:</label>
            <input type="email" id="email" name="email" required>
            <p id="email-error" style="color: red;"></p>
        </div>
        <button type="submit" name="update_email">Update Email</button>
    </form>

    <!-- Form to update password -->
    <form method="POST" action="user_settings.php">
        <div class="form-group">
            <label for="password">Update Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" name="update_password">Update Password</button>
    </form>
</div>

<?php
// Include the footer
include 'footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('email-error');
    const submitButton = document.querySelector('button[name="update_email"]');

    emailInput.addEventListener('input', function() {
        checkEmailAvailability(emailInput.value);
    });

    function checkEmailAvailability(email) {
        if (email === '') {
            emailError.textContent = '';
            submitButton.disabled = false;
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'user_settings.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.exists) {
                    emailError.textContent = 'Email is already taken.';
                    submitButton.disabled = true;
                } else {
                    emailError.textContent = '';
                    submitButton.disabled = false;
                }
            }
        };
        xhr.send('check_email=true&email=' + encodeURIComponent(email));
    }
});
</script>
