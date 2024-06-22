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

    // Fetch existing tests
    $tests = $pdo->query("SELECT id, test_name FROM tests")->fetchAll(PDO::FETCH_ASSOC);
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

        <h2>Assign Test to User</h2>
        <form method="POST" id="assignTestForm">
            <input type="hidden" name="user_id" id="assignTestUserId">
            <label for="test_id">Test:</label>
            <select name="test_id" id="assignTestId" required></select>
            <button type="submit" name="assign_test">Assign Test</button>
        </form>

        <h2>Remove Test from User</h2>
        <form method="POST" id="removeTestForm">
            <input type="hidden" name="user_id" id="removeTestUserId">
            <label for="test_id">Test:</label>
            <select name="test_id" id="removeTestId" required></select>
            <button type="submit" name="remove_test">Remove Test</button>
        </form>

        <h2>Assigned Tests</h2>
        <div id="assignedTests"></div>
    </div>
</div>

<script>
document.getElementById('searchForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const query = document.getElementById('searchQuery').value;
    fetch(`/includes/users/search_users.php?query=${query}`)
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('searchResults');
            resultsDiv.innerHTML = '';
            data.users.forEach(user => {
                const userDiv = document.createElement('div');
                userDiv.textContent = `${user.username} (${user.displayname})`;
                userDiv.addEventListener('click', () => {
                    document.getElementById('userId').value = user.id;
                    document.getElementById('displayName').value = user.displayname;
                    document.getElementById('role').value = user.role;
                    document.getElementById('assignTestUserId').value = user.id;
                    document.getElementById('removeTestUserId').value = user.id;

                    // Fetch assigned tests for this user
                    fetch(`/includes/tests/get_assigned_tests.php?user_id=${user.id}`)
                        .then(response => response.json())
                        .then(data => {
                            const assignedTestsDiv = document.getElementById('assignedTests');
                            assignedTestsDiv.innerHTML = '';
                            data.tests.forEach(test => {
                                const testDiv = document.createElement('div');
                                testDiv.textContent = test.test_name;
                                assignedTestsDiv.appendChild(testDiv);
                            });

                            const assignTestSelect = document.getElementById('assignTestId');
                            const removeTestSelect = document.getElementById('removeTestId');
                            assignTestSelect.innerHTML = '';
                            removeTestSelect.innerHTML = '';

                            // Populate the assign test select box with tests not assigned to the user
                            data.available_tests.forEach(test => {
                                const option = document.createElement('option');
                                option.value = test.id;
                                option.textContent = test.test_name;
                                assignTestSelect.appendChild(option);
                            });

                            // Populate the remove test select box with tests assigned to the user
                            data.tests.forEach(test => {
                                const option = document.createElement('option');
                                option.value = test.id;
                                option.textContent = test.test_name;
                                removeTestSelect.appendChild(option);
                            });
                        });
                    document.getElementById('userDetails').style.display = 'block';
                });
                resultsDiv.appendChild(userDiv);
            });
        });
});
</script>

<?php
include('../footer.php'); // Admin panel footer
?>
