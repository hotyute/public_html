<?php
// Start the session and check if the user is authenticated
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor', 'member'])) {
    header('Location: /login.php');
    exit();
}

require '../base_config.php';
require '../includes/database.php';

$userId = $_SESSION['user_id'];

$testHistoryQuery = "
    SELECT sc.id, sc.score, sc.percent, sc.taken_at, t.test_name
    FROM scores sc
    JOIN tests t ON sc.test_id = t.id
    WHERE sc.user_id = :userId
    ORDER BY sc.taken_at DESC
";
$stmt = $pdo->prepare($testHistoryQuery);
$stmt->execute(['userId' => $userId]);
$testHistoryResult = $stmt->fetchAll(PDO::FETCH_ASSOC);

$assignedTestsQuery = "
    SELECT ut.test_id, ut.assigned_at, t.test_name
    FROM user_tests ut
    JOIN tests t ON ut.test_id = t.id
    WHERE ut.user_id = :userId
    ORDER BY ut.assigned_at DESC
";
$stmt = $pdo->prepare($assignedTestsQuery);
$stmt->execute(['userId' => $userId]);
$assignedTestsResult = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <td><?= htmlspecialchars($row['test_name']) ?></td>
                    <td><?= htmlspecialchars($row['score']) ?></td>
                    <td>
                        <strong>
                            <span style="color:<?= ($row['percent'] >= 80 ? 'green' : 'red') ?>;">
                                <?= ($row['percent'] >= 80 ? 'PASS' : 'FAIL') ?>
                            </span>
                        </strong>
                    </td>
                    <td>
                        <span style="color:<?= ($row['percent'] >= 80 ? 'green' : 'red') ?>;">
                            <?= htmlspecialchars($row['percent']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['taken_at']) ?></td>
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
                <tr onclick="window.location.href='/test.php?test_id=<?= (int)$row['test_id'] ?>';" style="cursor:pointer;">
                    <td><?= htmlspecialchars($row['test_name']) ?></td>
                    <td><?= htmlspecialchars($row['assigned_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>