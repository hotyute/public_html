<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include('../header.php'); // Admin panel header
include('../includes/config.php'); // Database connection and other configuration

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle form submissions for assigning/removing tests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['assign_test'])) {
            $user_id = $_POST['user_id'];
            $test_id = $_POST['test_id'];

            $stmt = $pdo->prepare("INSERT INTO user_tests (user_id, test_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $test_id]);

            echo "Test assigned successfully!";
        } elseif (isset($_POST['remove_test'])) {
            $user_id = $_POST['user_id'];
            $test_id = $_POST['test_id'];

            $stmt = $pdo->prepare("DELETE FROM user_tests WHERE user_id = ? AND test_id = ?");
            $stmt->execute([$user_id, $test_id]);

            echo "Test removed successfully!";
        }
    }

    // Fetch existing users and tests
    $users = $pdo->query("SELECT id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $tests = $pdo->query("SELECT id, test_name FROM tests")->fetchAll(PDO::FETCH_ASSOC);
    $user_tests = $pdo->query("SELECT ut.user_id, ut.test_id, u.username, t.test_name FROM user_tests ut JOIN users u ON ut.user_id = u.id JOIN tests t ON ut.test_id = t.id")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="admin-container">
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
            <button type="submit">Update User</button>
        </form>
    </div>

    <h2>Assign Test to User</h2>
    <form method="POST">
        <label for="user_id">User:</label>
        <select name="user_id" id="user_id" required>
            <?php foreach ($users as $user): ?>
                <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['username']) ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <label for="test_id">Test:</label>
        <select name="test_id" id="test_id" required>
            <?php foreach ($tests as $test): ?>
                <option value="<?= htmlspecialchars($test['id']) ?>"><?= htmlspecialchars($test['test_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <button type="submit" name="assign_test">Assign Test</button>
    </form>

    <h2>Remove Test from User</h2>
    <form method="POST">
        <label for="user_id">User:</label>
        <select name="user_id" id="user_id" required>
            <?php foreach ($users as $user): ?>
                <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['username']) ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <label for="test_id">Test:</label>
        <select name="test_id" id="test_id" required>
            <?php foreach ($tests as $test): ?>
                <option value="<?= htmlspecialchars($test['id']) ?>"><?= htmlspecialchars($test['test_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <button type="submit" name="remove_test">Remove Test</button>
    </form>

    <h2>Assigned Tests</h2>
    <table border="1">
        <thead>
            <tr>
                <th>User</th>
                <th>Test</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($user_tests as $user_test): ?>
                <tr>
                    <td><?= htmlspecialchars($user_test['username']) ?></td>
                    <td><?= htmlspecialchars($user_test['test_name']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
include('../footer.php'); // Admin panel footer
?>
