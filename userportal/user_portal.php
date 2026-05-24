<?php
require_once __DIR__ . '/../includes/session.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'editor', 'member'], true)) {
    header('Location: /login.php');
    exit();
}

require_once __DIR__ . '/../base_config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/messages.php';
require_once __DIR__ . '/../includes/notifications/notification_data.php';

$userId = (int)$_SESSION['user_id'];
$displayName = $_SESSION['username'] ?? 'Member';

function portal_count(PDO $pdo, string $sql, array $params): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

$assignedCount = portal_count($pdo, 'SELECT COUNT(*) FROM user_tests WHERE user_id = ?', [$userId]);
$completedCount = portal_count($pdo, 'SELECT COUNT(*) FROM scores WHERE user_id = ?', [$userId]);
$unreadMessages = app_message_unread_count($userId);
$unreadNotifications = notification_unread_count($userId);
$recentMessages = app_message_recent_for_user($userId, 4);

$scoreStmt = $pdo->prepare("
    SELECT scores.score, scores.percent, scores.taken_at, tests.test_name
    FROM scores
    JOIN tests ON tests.id = scores.test_id
    WHERE scores.user_id = ?
    ORDER BY scores.taken_at DESC
    LIMIT 4
");
$scoreStmt->execute([$userId]);
$recentScores = $scoreStmt->fetchAll(PDO::FETCH_ASSOC);

$assignedStmt = $pdo->prepare("
    SELECT user_tests.test_id, user_tests.assigned_at, tests.test_name
    FROM user_tests
    JOIN tests ON tests.id = user_tests.test_id
    WHERE user_tests.user_id = ?
    ORDER BY user_tests.assigned_at DESC
    LIMIT 4
");
$assignedStmt->execute([$userId]);
$assignedTests = $assignedStmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../header.php';
?>
<div class="portal-dashboard">
    <section class="portal-hero">
        <div>
            <p class="section-kicker">Member Portal</p>
            <h1>Welcome back, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Keep track of messages, assigned tests, completed work, and account updates from one tidy place.</p>
        </div>
        <div class="portal-hero__actions">
            <a class="button" href="/userportal/messages.php">Open Messages</a>
            <a class="portal-secondary" href="/notifications.php">Notifications</a>
        </div>
    </section>

    <section class="portal-stat-grid" aria-label="Portal summary">
        <article class="portal-stat-card">
            <span>Unread Messages</span>
            <strong><?= number_format($unreadMessages) ?></strong>
        </article>
        <article class="portal-stat-card portal-stat-card--gold">
            <span>Notifications</span>
            <strong><?= number_format($unreadNotifications) ?></strong>
        </article>
        <article class="portal-stat-card portal-stat-card--green">
            <span>Assigned Tests</span>
            <strong><?= number_format($assignedCount) ?></strong>
        </article>
        <article class="portal-stat-card portal-stat-card--blue">
            <span>Tests Taken</span>
            <strong><?= number_format($completedCount) ?></strong>
        </article>
    </section>

    <div class="portal-grid">
        <section class="portal-card">
            <div class="portal-card__heading">
                <div>
                    <p class="section-kicker">Inbox</p>
                    <h2>Recent Messages</h2>
                </div>
                <a href="/userportal/messages.php">View All</a>
            </div>
            <?php if ($recentMessages): ?>
                <div class="portal-list">
                    <?php foreach ($recentMessages as $message): ?>
                        <a class="portal-list__item<?= (int)$message['is_read'] === 0 ? ' is-unread' : '' ?>" href="/userportal/messages.php?message_id=<?= (int)$message['id'] ?>">
                            <strong><?= htmlspecialchars($message['subject'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars($message['sender_name'], ENT_QUOTES, 'UTF-8') ?> &middot; <?= htmlspecialchars(date('M j, H:i', strtotime($message['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="portal-empty">No messages yet.</p>
            <?php endif; ?>
        </section>

        <section class="portal-card">
            <div class="portal-card__heading">
                <div>
                    <p class="section-kicker">Learning</p>
                    <h2>Assigned Tests</h2>
                </div>
                <a href="/userportal/test_history.php">History</a>
            </div>
            <?php if ($assignedTests): ?>
                <div class="portal-list">
                    <?php foreach ($assignedTests as $test): ?>
                        <a class="portal-list__item" href="/test.php?test_id=<?= (int)$test['test_id'] ?>">
                            <strong><?= htmlspecialchars($test['test_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span>Assigned <?= htmlspecialchars(date('M j, Y', strtotime($test['assigned_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="portal-empty">No tests are currently assigned.</p>
            <?php endif; ?>
        </section>
    </div>

    <section class="portal-card">
        <div class="portal-card__heading">
            <div>
                <p class="section-kicker">Recent Results</p>
                <h2>Test Activity</h2>
            </div>
            <a href="/userportal/user_settings.php">Account Settings</a>
        </div>
        <?php if ($recentScores): ?>
            <div class="portal-results">
                <?php foreach ($recentScores as $score): ?>
                    <?php $passed = (float)$score['percent'] >= 80; ?>
                    <article>
                        <strong><?= htmlspecialchars($score['test_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="<?= $passed ? 'is-pass' : 'is-fail' ?>"><?= $passed ? 'PASS' : 'REVIEW' ?> &middot; <?= htmlspecialchars((string)$score['percent'], ENT_QUOTES, 'UTF-8') ?>%</span>
                        <small><?= htmlspecialchars(date('M j, Y', strtotime($score['taken_at'])), ENT_QUOTES, 'UTF-8') ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="portal-empty">Completed test results will appear here.</p>
        <?php endif; ?>
    </section>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
