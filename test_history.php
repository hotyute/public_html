<?php
// Start the session and check if the user is authenticated and is an admin.
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor', 'member'])) {
    header('Location: /login.php'); // Redirect to login if not authenticated as admin.
    exit();
}

// Include header file
include 'header.php';

// Include database connection
require 'database.php';

// Fetch test history
$userId = $_SESSION['user_id'];
$testHistoryQuery = "
    SELECT th.id, th.score, th.taken_at, t.name AS test_name 
    FROM test_history th 
    JOIN tests t ON th.test_id = t.id 
    WHERE th.user_id = :userId";
$stmt = $pdo->prepare($testHistoryQuery);
$stmt->execute(['userId' => $userId]);
$testHistoryResult = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch currently assigned tests
$assignedTestsQuery = "
    SELECT at.id, at.assigned_at, t.name AS test_name 
    FROM assigned_tests at 
    JOIN tests t ON at.test_id = t.id 
    WHERE at.user_id = :userId";
$stmt = $pdo->prepare($assignedTestsQuery);
$stmt->execute(['userId' => $userId]);
$assignedTestsResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="test-container">
    <h1>Test History</h1>
    <table class="test-table">
        <thead>
            <tr>
                <th>Test Name</th>
                <th>Score</th>
                <th>Status</th>
                <th>Taken At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($testHistoryResult as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['test_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['score']); ?></td>
                    <td><?php echo $row['score'] >= 50 ? 'PASS' : 'FAIL'; ?></td>
                    <td><?php echo htmlspecialchars($row['taken_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h1>Currently Assigned Tests</h1>
    <table class="test-table">
        <thead>
            <tr>
                <th>Test Name</th>
                <th>Assigned At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignedTestsResult as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['test_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['assigned_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
// Include footer file
include 'footer.php';
?>
