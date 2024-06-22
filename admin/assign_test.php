<?php
session_start();
require_once '../base_config.php';
require 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Unauthorized access.");
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_POST['user_id'];
        $test_id = $_POST['test_id'];

        $stmt = $pdo->prepare("INSERT INTO user_tests (user_id, test_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $test_id]);

        echo "Test assigned successfully!";
    } else {
        $users = $pdo->query("SELECT id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);
        $tests = $pdo->query("SELECT id, test_name FROM tests")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<?php include '../header.php'; ?>
<h1>Assign Tests to Users</h1>
<form method="POST">
    <label for="user_id">User:</label>
    <select name="user_id" id="user_id" required>
        <?php foreach ($users as $user) : ?>
            <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['username']) ?></option>
        <?php endforeach; ?>
    </select>
    <br>
    <label for="test_id">Test:</label>
    <select name="test_id" id="test_id" required>
        <?php foreach ($tests as $test) : ?>
            <option value="<?= htmlspecialchars($test['id']) ?>"><?= htmlspecialchars($test['test_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <br>
    <button type="submit">Assign Test</button>
</form>
<?php include '../footer.php'; ?>