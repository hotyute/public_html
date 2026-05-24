<?php
// admin/admin_panel.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';

$userRole = $_SESSION['user_role'] ?? '';
if (!in_array($userRole, ['admin', 'editor'], true)) {
    header('Location: /login.php');
    exit();
}

$canManageSite = $userRole === 'admin';

function admin_dashboard_count(PDO $pdo, string $table): int
{
    $allowedTables = ['posts', 'comments', 'users', 'magazine_articles', 'tests'];
    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function admin_dashboard_date(?string $date): string
{
    if (!$date) {
        return 'Unknown';
    }

    $timestamp = strtotime($date);
    return $timestamp ? date('M j, Y', $timestamp) : 'Unknown';
}

$stats = [
    [
        'label' => 'Published Articles',
        'value' => admin_dashboard_count($pdo, 'posts'),
        'accent' => 'gold',
    ],
    [
        'label' => 'Comments',
        'value' => admin_dashboard_count($pdo, 'comments'),
        'accent' => 'blue',
    ],
    [
        'label' => 'Members',
        'value' => admin_dashboard_count($pdo, 'users'),
        'accent' => 'green',
    ],
    [
        'label' => 'External Articles',
        'value' => admin_dashboard_count($pdo, 'magazine_articles'),
        'accent' => 'red',
    ],
];

try {
    $recentPostsStmt = $pdo->query("
        SELECT posts.id, posts.title, posts.created_at, COALESCE(users.displayname, 'Unknown') AS author
        FROM posts
        LEFT JOIN users ON posts.user_id = users.id
        ORDER BY posts.created_at DESC, posts.id DESC
        LIMIT 5
    ");
    $recentPosts = $recentPostsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $recentPosts = [];
}

$quickActions = [
    [
        'label' => 'Create Article',
        'description' => 'Write, upload media, and generate article audio.',
        'href' => 'create_post.php',
        'adminOnly' => false,
    ],
    [
        'label' => 'Edit Articles',
        'description' => 'Update existing posts, thumbnails, layout, and audio.',
        'href' => 'edit_post.php',
        'adminOnly' => false,
    ],
    [
        'label' => 'Video of the Week',
        'description' => 'Preview and tune the homepage featured video.',
        'href' => 'edit_video.php',
        'adminOnly' => false,
    ],
    [
        'label' => 'External Magazines',
        'description' => 'Manage magazine previews and external links.',
        'href' => 'manage_magazines.php',
        'adminOnly' => true,
    ],
    [
        'label' => 'Manage Users',
        'description' => 'Review members, roles, and test assignments.',
        'href' => 'manage_users.php',
        'adminOnly' => true,
    ],
    [
        'label' => 'Manage Tests',
        'description' => 'Build and maintain member learning tests.',
        'href' => 'test_manage.php',
        'adminOnly' => true,
    ],
    [
        'label' => 'Delete Articles',
        'description' => 'Remove articles and generated audio files.',
        'href' => 'delete_post.php',
        'adminOnly' => true,
    ],
];

include '../header.php';
?>
<div class="admin-dashboard">
    <section class="admin-dashboard__hero">
        <div>
            <p class="section-kicker">Site Management</p>
            <h1>Admin Dashboard</h1>
            <p>Manage articles, media, magazines, members, and homepage features from one focused workspace.</p>
        </div>
        <div class="admin-dashboard__hero-actions">
            <a class="button" href="/index.php">View Homepage</a>
            <a class="admin-dashboard__secondary" href="edit_post.php">Edit Articles</a>
        </div>
    </section>

    <section class="admin-stat-grid" aria-label="Site totals">
        <?php foreach ($stats as $stat): ?>
            <article class="admin-stat-card admin-stat-card--<?= htmlspecialchars($stat['accent'], ENT_QUOTES, 'UTF-8') ?>">
                <span><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <strong><?= number_format((int)$stat['value']) ?></strong>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="admin-dashboard__main">
        <section class="admin-panel-card">
            <div class="admin-panel-card__heading">
                <div>
                    <p class="section-kicker">Shortcuts</p>
                    <h2>Common Actions</h2>
                </div>
            </div>

            <div class="admin-action-grid">
                <?php foreach ($quickActions as $action): ?>
                    <?php if ($action['adminOnly'] && !$canManageSite) {
                        continue;
                    } ?>
                    <a class="admin-action-card" href="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <strong><?= htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($action['description'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="admin-panel-card admin-panel-card--compact">
            <div class="admin-panel-card__heading">
                <div>
                    <p class="section-kicker">Recently Published</p>
                    <h2>Latest Articles</h2>
                </div>
                <a href="/archive.php">All Posts</a>
            </div>

            <?php if ($recentPosts): ?>
                <ol class="admin-recent-list">
                    <?php foreach ($recentPosts as $post): ?>
                        <li>
                            <a href="/post.php?id=<?= (int)$post['id'] ?>">
                                <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <span>
                                <?= htmlspecialchars($post['author'], ENT_QUOTES, 'UTF-8') ?>
                                &middot;
                                <?= htmlspecialchars(admin_dashboard_date($post['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="admin-empty-state">No articles have been published yet.</p>
            <?php endif; ?>
        </aside>
    </div>

    <section class="admin-panel-card admin-dashboard__notice">
        <div>
            <p class="section-kicker">Publishing Flow</p>
            <h2>Before Going Live</h2>
        </div>
        <p>Preview the homepage after content changes, then open the article page to confirm image sizing, inline edits, and generated audio playback.</p>
    </section>
</div>
<?php include '../footer.php'; ?>
