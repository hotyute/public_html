<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/notifications/notification_data.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(400);
            die('Invalid CSRF token');
        }

        if (isset($_POST['assign_test'])) {
            $user_id = (int)$_POST['user_id'];
            $test_id = (int)$_POST['test_id'];
            $test_name = trim($_POST['test_name'] ?? '');

            $stmt = $pdo->prepare("INSERT INTO user_tests (user_id, test_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $test_id]);

            $message = "Test '{$test_name}' assigned successfully! Take the test at <a href='/test.php?test_id={$test_id}' style='color: blue; font-size: 1.0em; font-weight: bold;'>this link</a>";
            add_notification($user_id, "Test Assigned", $message);

            echo "Test {$test_name} assigned successfully!";
        } elseif (isset($_POST['remove_test'])) {
            $user_id = (int)$_POST['user_id'];
            $test_id = (int)$_POST['test_id'];
            $test_name = trim($_POST['test_name'] ?? '');

            $stmt = $pdo->prepare("DELETE FROM user_tests WHERE user_id = ? AND test_id = ?");
            $stmt->execute([$user_id, $test_id]);

            $message = "Test '{$test_name}' removed successfully.";
            add_notification($user_id, "Test Removed", $message);

            echo "Test removed successfully!";
        }
    }

    $tests = $pdo->query("SELECT id, test_name FROM tests")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include '../header.php';
?>
<div class="musers-container">
    <h2>User Management</h2>
    <form id="searchForm">
        <input type="text" id="searchQuery" placeholder="Search by username or display name">
        <button type="submit">Search</button>
    </form>

    <div id="searchResults"></div>
    <div id="userDetails" style="display: none;">
        <form id="editUserForm">
            <input type="hidden" id="userId" name="userId">
            <label for="displayName">Display Name:</label>
            <input type="text" id="displayName" name="displayName">
            <label for="role">Role:</label>
            <select id="role" name="role">
                <option value="admin">Admin</option>
                <option value="editor">Editor</option>
                <option value="member">Member</option>
            </select>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit">Update User</button>
        </form>

        <h2>Assign Test to User</h2>
        <form method="POST" id="assignTestForm">
            <input type="hidden" name="user_id" id="assignTestUserId">
            <label for="test_id">Test:</label>
            <select name="test_id" id="assignTestId" required></select>
            <input type="hidden" name="test_name" id="assignTestName">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit" name="assign_test">Assign Test</button>
        </form>

        <h2>Remove Test from User</h2>
        <form method="POST" id="removeTestForm">
            <input type="hidden" name="user_id" id="removeTestUserId">
            <label for="test_id">Test:</label>
            <select name="test_id" id="removeTestId" required></select>
            <input type="hidden" name="test_name" id="removeTestName">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit" name="remove_test">Remove Test</button>
        </form>

        <h2>Assigned Tests</h2>
        <div id="assignedTests"></div>
    </div>
</div>
<?php include '../footer.php'; ?>