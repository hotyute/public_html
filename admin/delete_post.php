<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

require_once '../includes/database.php';
require_once '../includes/session.php';

$status_message = '';
$status_color = 'red';

function fetch_posts(PDO $pdo): array
{
    $stmt = $pdo->prepare(
        "SELECT id, title, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS formatted_date, thumbnail FROM posts ORDER BY created_at DESC"
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        die('Invalid CSRF token');
    }

    $post_id = (int)($_POST['post_id'] ?? 0);

    if ($post_id <= 0) {
        $status_message = 'Invalid post selection.';
    } else {
        $lookup = $pdo->prepare('SELECT title, thumbnail FROM posts WHERE id = ?');
        $lookup->execute([$post_id]);
        $post = $lookup->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            $status_message = 'Selected post could not be found.';
        } else {
            try {
                $delete = $pdo->prepare('DELETE FROM posts WHERE id = ?');
                $delete->execute([$post_id]);

                if ($delete->rowCount() > 0) {
                    $status_message = 'Post "' . $post['title'] . '" deleted successfully!';
                    $status_color = 'green';

                    if (!empty($post['thumbnail']) && file_exists($post['thumbnail'])) {
                        @unlink($post['thumbnail']);
                    }
                } else {
                    $status_message = 'Failed to delete the selected post.';
                }
            } catch (PDOException $e) {
                $status_message = 'Failed to delete the selected post.';
            }
        }
    }
}

$posts = fetch_posts($pdo);

include '../header.php';
?>

<div class="admin-content" style="max-width: 960px; margin: 40px auto;">
    <h2 style="text-align:center;">Delete Post</h2>

    <?php if ($status_message !== ''): ?>
        <p style="text-align:center;color: <?= $status_color ?>;">
            <?= htmlspecialchars($status_message) ?>
        </p>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <p style="text-align:center;">No posts available to delete.</p>
    <?php else: ?>
        <form method="POST" action="delete_post.php" onsubmit="return confirm('Are you sure you want to delete this post?');" class="admin-form">
            <label for="post_id">Select a post to delete:</label>
            <select name="post_id" id="post_id" required style="width:100%; padding: 8px; margin: 12px 0;">
                <option value="">-- Choose a post --</option>
                <?php foreach ($posts as $post): ?>
                    <option value="<?= (int)$post['id'] ?>">
                        <?= htmlspecialchars($post['title']) ?> (<?= htmlspecialchars($post['formatted_date']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit" style="background:#c0392b;color:#fff;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;">Delete Post</button>
        </form>

        <div style="margin-top: 30px;">
            <h3>Recent posts</h3>
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding: 8px; border-bottom: 1px solid #ddd;">Title</th>
                        <th style="text-align:left; padding: 8px; border-bottom: 1px solid #ddd;">Created</th>
                        <th style="text-align:left; padding: 8px; border-bottom: 1px solid #ddd;">Thumbnail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;">
                                <?= htmlspecialchars($post['title']) ?>
                            </td>
                            <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;">
                                <?= htmlspecialchars($post['formatted_date']) ?>
                            </td>
                            <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;">
                                <?php if (!empty($post['thumbnail'])): ?>
                                    <img src="<?= htmlspecialchars(str_replace('../', '/', $post['thumbnail'])) ?>" alt="Thumbnail" style="max-width: 120px; border-radius:4px; border:1px solid #ddd;">
                                <?php else: ?>
                                    <em>None</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>
