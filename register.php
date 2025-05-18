<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'includes/database.php';  // Include the database connection
require 'includes/sanitize.php'; // Include the sanitization function

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input
    $username = htmlspecialchars(sanitize_html($_POST['username']));
    $password = htmlspecialchars($_POST['password']);
    $displayname = htmlspecialchars(sanitize_html($_POST['displayname']));
    $email = htmlspecialchars($_POST['email']);  // Sanitize the email
    $role = "member";

    // Check if the username or email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        $error_message = "Username or Email already exists!";
    } else {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into the database including email
        $insert_stmt = $pdo->prepare("INSERT INTO users (username, displayname, email, password, role) VALUES (?, ?, ?, ?, ?)");
        if ($insert_stmt->execute([$username, $displayname, $email, $password_hash, $role])) {
            $success_message = "User registered successfully!";
        } else {
            $error_message = "Failed to register user.";
        }
    }
}
?>
<?php include 'header.php'; ?>

<div class="register-container">
    <h1>Register User</h1>
    <?php if (!empty($error_message)): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error_message) ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
        <p style="color: green;">
            <?= htmlspecialchars($success_message) ?>
        </p>
    <?php endif; ?>
    <form method="POST" action="register.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" placeholder="Desired username (ex: starshooter10)" required><br>

        <label for="displayname">Full Name (First &amp; Last Name):</label>
        <input type="text" id="displayname" name="displayname" placeholder="John Smith" required>
        <small>Please enter your first and last name.</small><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>

        <button type="submit">Register</button>
    </form>
</div>

<?php include 'footer.php'; ?>

<!-- Include the JavaScript code -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const displaynameInput = document.getElementById('displayname');
        const submitButton = document.querySelector('button');

        // Limit username to 25 characters
        usernameInput.maxLength = 25;
        
        // Limit displayname to 50 characters
        displaynameInput.maxLength = 50;

        // Limit email to 50 characters
        emailInput.maxLength = 50;

        // Disable submit button initially
        submitButton.disabled = true;

        // Enable submit button only when all fields are filled
        function checkFormValidity() {
            if (usernameInput.value && passwordInput.value && displaynameInput.value && emailInput.value) {
                submitButton.disabled = false;
            } else {
                submitButton.disabled = true;
            }
        }
        
        // Check form validity on input
        usernameInput.addEventListener('input', checkFormValidity);
        emailInput.addEventListener('input', checkFormValidity);
        passwordInput.addEventListener('input', checkFormValidity);
        displaynameInput.addEventListener('input', checkFormValidity);

        // Show tooltip if character limit exceeded or input is invalid
        function showTooltip(inputElement, message) {
            let tooltip = inputElement.nextElementSibling;
            if (!tooltip || !tooltip.classList.contains('char-limit-tooltip')) {
                tooltip = document.createElement('div');
                tooltip.classList.add('char-limit-tooltip');
                tooltip.style.position = 'absolute';
                tooltip.style.backgroundColor = '#f8d7da';
                tooltip.style.color = '#721c24';
                tooltip.style.padding = '5px';
                tooltip.style.borderRadius = '5px';
                tooltip.style.top = `${inputElement.offsetTop - 30}px`;
                tooltip.style.left = `${inputElement.offsetLeft}px`;
                tooltip.style.zIndex = '1000';
                inputElement.insertAdjacentElement('afterend', tooltip);
            }
            tooltip.textContent = message;
        }

        function hideTooltip(inputElement) {
            const tooltip = inputElement.nextElementSibling;
            if (tooltip && tooltip.classList.contains('char-limit-tooltip')) {
                tooltip.remove();
            }
        }

        usernameInput.addEventListener('input', function() {
            if (usernameInput.value.length > 25) {
                showTooltip(usernameInput, 'Maximum 25 characters allowed');
            } else if (!sanitizeHtml(usernameInput.value)) {
                showTooltip(usernameInput, 'Invalid characters in username');
            } else {
                hideTooltip(usernameInput);
            }
        });

        displaynameInput.addEventListener('input', function() {
            if (displaynameInput.value.length > 50) {
                showTooltip(displaynameInput, 'Maximum 50 characters allowed');
            } else if (!sanitizeHtml(displaynameInput.value)) {
                showTooltip(displaynameInput, 'Invalid characters in display name');
            } else {
                hideTooltip(displaynameInput);
            }
        });

        emailInput.addEventListener('input', function() {
            if (emailInput.value.length > 50) {
                showTooltip(emailInput, 'Maximum 50 characters allowed');
            } else if (!sanitizeHtml(emailInput.value)) {
                showTooltip(emailInput, 'Invalid characters in display name');
            } else {
                hideTooltip(emailInput);
            }
        });

        function sanitizeHtml(input) {
            const div = document.createElement('div');
            div.textContent = input;
            return div.innerHTML === input;
        }

        // Password strength checker
        passwordInput.addEventListener('input', function() {
            let strengthIndicator = passwordInput.nextElementSibling;
            if (!strengthIndicator || !strengthIndicator.classList.contains('password-strength-indicator')) {
                strengthIndicator = document.createElement('span');
                strengthIndicator.classList.add('password-strength-indicator');
                strengthIndicator.style.display = 'block';
                strengthIndicator.style.marginTop = '10px';
                passwordInput.insertAdjacentElement('afterend', strengthIndicator);
            }

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
</style>
