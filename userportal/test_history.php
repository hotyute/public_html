<?php
// Start the session and check if the user is authenticated and is an admin.
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor', 'member'])) {
    header('Location: /login.php'); // Redirect to login if not authenticated as admin.
    exit();
}

// Include database connection
require '../base_config.php';
require 'includes/database.php';
require_once 'includes/database.php';

// Fetch test history
$userId = $_SESSION['user_id'];
$testHistoryQuery = "
    SELECT th.id, th.score, th.percent, th.taken_at, t.test_name AS test_name 
    FROM scores th 
    JOIN tests t ON th.test_id = t.id 
    WHERE th.user_id = :userId";
$stmt = $pdo->prepare($testHistoryQuery);
$stmt->execute(['userId' => $userId]);
$testHistoryResult = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch currently assigned tests
$assignedTestsQuery = "
    SELECT at.id, at.assigned_at, t.test_name AS test_name 
    FROM user_tests at 
    JOIN tests t ON at.test_id = t.id 
    WHERE at.user_id = :userId";
$stmt = $pdo->prepare($assignedTestsQuery);
$stmt->execute(['userId' => $userId]);
$assignedTestsResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
// Include header file
include 'header.php';
?>

<div class="test-container">
    <h1>Test History</h1>
    <table class="test-table">
        <thead>
            <tr>
                <th>Test Name</th>
                <th>Score</th>
                <th>Status</th>
                <th>Percent</th>
                <th>Taken At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($testHistoryResult as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['test_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['score']); ?></td>
                    <td><?php echo '<strong><span style="color:' . ($row['percent'] >= 80 ? 'green;">PASS' : 'red;">FAIL') . '</span></strong>'; ?></td>
                    <td><?php echo '<span style="color:' . ($row['percent'] >= 80 ? 'green;">' : 'red;">') . $row['percent'] . '</span>'; ?></td>
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
                <tr onclick="window.location.href='/test.php?test_id=<?php echo $row['id']; ?>';" style="cursor:pointer;">
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