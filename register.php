<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'includes/database.php';   // Include the database connection
require 'includes/sanitize.php';   // Include the sanitization function

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect raw inputs
    $rawUsername    = trim($_POST['username'] ?? '');
    $rawDisplayName = trim($_POST['displayname'] ?? '');
    $rawEmail       = trim($_POST['email'] ?? '');
    $rawPassword    = $_POST['password'] ?? '';

    // Validate length & format
    $errors = [];

    // Username
    if ($rawUsername === '') {
        $errors[] = "Username is required.";
    } elseif (mb_strlen($rawUsername) > 25) {
        $errors[] = "Username must be 25 characters or fewer.";
    }

    // Display name
    if ($rawDisplayName === '') {
        $errors[] = "Full name is required.";
    } elseif (mb_strlen($rawDisplayName) > 50) {
        $errors[] = "Full name must be 50 characters or fewer.";
    }

    // Email
    if ($rawEmail === '') {
        $errors[] = "Email is required.";
    } elseif (mb_strlen($rawEmail) > 50) {
        $errors[] = "Email must be 50 characters or fewer.";
    } elseif (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email address is not valid.";
    }

    // Password length: require between 8 and 128
    $pwLen = mb_strlen($rawPassword);
    if ($pwLen === 0) {
        $errors[] = "Password is required.";
    } elseif ($pwLen < 8) {
        $errors[] = "Password must be at least 8 characters.";
    } elseif ($pwLen > 128) {
        $errors[] = "Password cannot exceed 128 characters.";
    }

    // If any errors, don't proceed
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    } else {
        // Sanitize for HTML output
        $username    = htmlspecialchars(sanitize_html($rawUsername),    ENT_QUOTES, 'UTF-8');
        $displayname = htmlspecialchars(sanitize_html($rawDisplayName), ENT_QUOTES, 'UTF-8');
        $email       = htmlspecialchars($rawEmail,                      ENT_QUOTES, 'UTF-8');
        $password    = $rawPassword;
        $role        = "member";

        // Check uniqueness
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error_message = "Username or Email already exists!";
        } else {
            // Hash & insert
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $pdo->prepare("
                INSERT INTO users (username, displayname, email, password, role)
                VALUES (?, ?, ?, ?, ?)
            ");
            if ($insert_stmt->execute([$username, $displayname, $email, $password_hash, $role])) {
                $success_message = "User registered successfully!";
            } else {
                $error_message = "Failed to register user.";
            }
        }
    }
}
?>
<?php include 'header.php'; ?>

<div class="register-container">
    <h1>Register User</h1>
    <?php if (!empty($error_message)): ?>
        <p style="color: red;"><?= $error_message ?></p>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
        <p style="color: green;"><?= $success_message ?></p>
    <?php endif; ?>
    <form method="POST" action="register.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" placeholder="Desired username (ex: starshooter10)" required><br>

        <label for="displayname">Full Name (First &amp; Last Name):</label>
        <input type="text" id="displayname" name="displayname" placeholder="John Smith" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>

        <label for="password">Password:</label>
        <div class="password-wrapper">
            <input type="password" id="password" name="password" required>
            <span class="password-strength-indicator"></span>
        </div>
        <br>

        <button type="submit">Register</button>
    </form>
</div>

<?php include 'footer.php'; ?>

<!-- Include the JavaScript code -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const usernameInput = document.getElementById('username');
        const displaynameInput = document.getElementById('displayname');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const submitButton = document.querySelector('button');

        // Set max lengths
        usernameInput.maxLength = 25;
        displaynameInput.maxLength = 50;
        emailInput.maxLength = 50;
        passwordInput.maxLength = 128;

        // Disable submit until all fields non-empty
        function checkFormValidity() {
            submitButton.disabled = !(
                usernameInput.value &&
                displaynameInput.value &&
                emailInput.value &&
                passwordInput.value
            );
        }
        [usernameInput, displaynameInput, emailInput, passwordInput]
        .forEach(el => el.addEventListener('input', checkFormValidity));

        // Tooltip helper
        function showTooltip(el, msg) {
            let tt = el.nextElementSibling;
            if (!tt || !tt.classList.contains('char-limit-tooltip')) {
                tt = document.createElement('div');
                tt.classList.add('char-limit-tooltip');
                tt.style.position = 'absolute';
                tt.style.backgroundColor = '#f8d7da';
                tt.style.color = '#721c24';
                tt.style.padding = '5px';
                tt.style.borderRadius = '5px';
                tt.style.top = (el.offsetTop - 30) + 'px';
                tt.style.left = el.offsetLeft + 'px';
                tt.style.zIndex = 1000;
                el.insertAdjacentElement('afterend', tt);
            }
            tt.textContent = msg;
        }

        function hideTooltip(el) {
            const tt = el.nextElementSibling;
            if (tt && tt.classList.contains('char-limit-tooltip')) {
                tt.remove();
            }
        }

        // Field-specific length/format checks
        usernameInput.addEventListener('input', () => {
            if (usernameInput.value.length > 25)
                showTooltip(usernameInput, 'Maximum 25 characters allowed');
            else
                hideTooltip(usernameInput);
        });
        displaynameInput.addEventListener('input', () => {
            if (displaynameInput.value.length > 50)
                showTooltip(displaynameInput, 'Maximum 50 characters allowed');
            else
                hideTooltip(displaynameInput);
        });
        emailInput.addEventListener('input', () => {
            if (emailInput.value.length > 50)
                showTooltip(emailInput, 'Maximum 50 characters allowed');
            else
                hideTooltip(emailInput);
        });

        // Password length tooltip and strength
        passwordInput.addEventListener('input', () => {
            const len = passwordInput.value.length;
            if (len > 128) {
                showTooltip(passwordInput, 'Maximum 128 characters allowed');
            } else if (len > 0 && len < 8) {
                showTooltip(passwordInput, 'Must be at least 8 characters');
            } else {
                hideTooltip(passwordInput);
            }

            // Strength indicator (unchanged)
            let ind = passwordInput.nextElementSibling;
            if (!ind || !ind.classList.contains('password-strength-indicator')) {
                ind = document.createElement('span');
                ind.classList.add('password-strength-indicator');
                ind.style.display = 'block';
                ind.style.marginTop = '10px';
                passwordInput.insertAdjacentElement('afterend', ind);
            }
            const pwd = passwordInput.value;
            let strength = '';
            if (pwd.length >= 10) strength = 'strong';
            else if (pwd.length >= 6) strength = 'medium';
            else if (pwd.length > 0) strength = 'weak';

            switch (strength) {
                case 'weak':
                    ind.textContent = 'Password Strength: Weak';
                    ind.style.color = '#e74c3c';
                    break;
                case 'medium':
                    ind.textContent = 'Password Strength: Medium';
                    ind.style.color = '#f39c12';
                    break;
                case 'strong':
                    ind.textContent = 'Password Strength: Strong';
                    ind.style.color = '#2ecc71';
                    break;
                default:
                    ind.textContent = '';
            }
        });

        // Initial disable
        submitButton.disabled = true;
    });
</script>

<style>
    .char-limit-tooltip {
        position: absolute;
        background-color: #f8d7da;
        color: #721c24;
        padding: 5px;
        border-radius: 5px;
        font-size: 12px;
        box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.2);
    }

    .password-wrapper {
        display: inline-flex;
        align-items: center;
    }

    .password-strength-indicator {
        margin-left: 10px;
    }
</style>