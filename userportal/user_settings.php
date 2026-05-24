<?php
require_once '../includes/session.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

require_once '../base_config.php';
require __DIR__ . '/../includes/database.php';

// Check if the request is for email validation via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_email'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        echo json_encode(['exists' => false, 'error' => 'Invalid CSRF token']);
        exit();
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $user_id = $_SESSION['user_id'];

    if (!$email) {
        echo json_encode(['exists' => false]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetchColumn()) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit();
}

// Handle form submission for updating email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error_message = "Invalid CSRF token.";
    } else {
        $new_email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$new_email) {
            $error_message = "Please enter a valid email address.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$new_email, $_SESSION['user_id']]);
            if ($stmt->fetchColumn()) {
                $error_message = "Email is already taken.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                if ($stmt->execute([$new_email, $_SESSION['user_id']])) {
                    $success_message = "Email updated successfully!";
                } else {
                    $error_message = "Failed to update email.";
                }
            }
        }
    }
}

// Handle form submission for updating password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error_message = "Invalid CSRF token.";
    } else {
        $raw_password = $_POST['password'] ?? '';

        if (strlen($raw_password) < 8 || strlen($raw_password) > 128) {
            $error_message = "Password must be between 8 and 128 characters.";
        } else {
            $new_password = password_hash($raw_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$new_password, $_SESSION['user_id']])) {
                app_regenerate_session(true);
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Failed to update password.";
            }
        }
    }
}

// Include necessary files and the header
include __DIR__ . '/../header.php';
?>

<div class="settings-container">
    <h1>User Settings</h1>

    <?php if (!empty($error_message)): ?>
        <p style="color: red;"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
        <p style="color: green;"><?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <!-- Form to update email -->
    <form id="email-form" method="POST" action="user_settings.php">
        <div class="form-group">
            <label for="email">Update Email:</label>
            <input type="email" id="email" name="email" required>
            <p id="email-error" style="color: red;"></p>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" name="update_email">Update Email</button>
    </form>

    <!-- Form to update password -->
    <form method="POST" action="user_settings.php">
        <div class="form-group">
            <label for="password">Update Password:</label>
            <input type="password" id="password" name="password" minlength="8" maxlength="128" required>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" name="update_password">Update Password</button>
    </form>
</div>

<?php
// Include the footer
include __DIR__ . '/../footer.php';
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
        xhr.send('check_email=true&csrf_token=<?= htmlspecialchars(rawurlencode($csrf_token), ENT_QUOTES, 'UTF-8') ?>&email=' + encodeURIComponent(email));
    }
});
</script>
