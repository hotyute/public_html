<?php
include 'header.php';
require 'includes/database.php';

$default_posts_per_page = 10;
$allowed_posts_per_page = [10, 20, 30, 100];
$posts_per_page = isset($_GET['posts_per_page']) ? (int)$_GET['posts_per_page'] : $default_posts_per_page;
if (!in_array($posts_per_page, $allowed_posts_per_page, true)) {
    $posts_per_page = $default_posts_per_page;
}
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$offset = ($page - 1) * $posts_per_page;

$total_posts_query = "SELECT COUNT(*) FROM posts";
$total_posts_result = $pdo->query($total_posts_query);
$total_posts = $total_posts_result->fetchColumn();

$query = "SELECT posts.id, posts.title, posts.thumbnail, COALESCE(users.displayname, 'Unknown') AS author, posts.created_at
          FROM posts
          LEFT JOIN users ON posts.user_id = users.id
          ORDER BY posts.id DESC
          LIMIT :limit OFFSET :offset";
$posts = $pdo->prepare($query);
$posts->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
$posts->bindValue(':offset', $offset, PDO::PARAM_INT);
$posts->execute();
?>
<div class="main-container">
    <main>
        <section>
            <h2>Archive</h2>
            <form method="GET" action="archive.php">
                <label for="posts_per_page">Posts per page:</label>
                <select id="posts_per_page" name="posts_per_page" onchange="this.form.submit()">
                    <option value="10" <?php if ($posts_per_page == 10) echo 'selected'; ?>>10</option>
                    <option value="20" <?php if ($posts_per_page == 20) echo 'selected'; ?>>20</option>
                    <option value="30" <?php if ($posts_per_page == 30) echo 'selected'; ?>>30</option>
                    <option value="100" <?php if ($posts_per_page == 100) echo 'selected'; ?>>100</option>
                </select>
            </form>
            <ul class="archive-list">
                <?php while ($post = $posts->fetch(PDO::FETCH_ASSOC)): ?>
                    <li class="archive-item">
                        <?php if ($post['thumbnail']): ?>
                            <?php $thumbnailPath = str_replace('../', '/', $post['thumbnail']); ?>
                            <img src="<?= htmlspecialchars($thumbnailPath) ?>" alt="<?= htmlspecialchars($post['title']) ?> Thumbnail" class="archive-thumbnail">
                        <?php endif; ?>
                        <div class="archive-details">
                            <a href="post.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a><br>
                            by <?= htmlspecialchars($post['author']) ?>
                            on <?= date('F j, Y', strtotime($post['created_at'])) ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
            <div class="pagination">
                <?php
                $total_pages = max(1, (int)ceil($total_posts / $posts_per_page));
                if ($page > 1) {
                    echo '<a href="?page=' . ($page - 1) . '&posts_per_page=' . $posts_per_page . '">Previous</a>';
                }
                if ($page < $total_pages) {
                    echo '<a href="?page=' . ($page + 1) . '&posts_per_page=' . $posts_per_page . '">Next</a>';
                }
                ?>
            </div>
        </section>
    </main>
</div>
<?php include 'footer.php'; ?>
