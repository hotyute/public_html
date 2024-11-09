<?php
include 'header.php';
require 'includes/database.php';

// Set default posts per page
$default_posts_per_page = 10;

// Get the selected number of posts per page from the dropdown, or use the default
$posts_per_page = isset($_GET['posts_per_page']) ? (int)$_GET['posts_per_page'] : $default_posts_per_page;

// Get the current page number from the URL, default to 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate the offset for the SQL query
$offset = ($page - 1) * $posts_per_page;

// Fetch the total number of posts
$total_posts_query = "SELECT COUNT(*) FROM posts";
$total_posts_result = $pdo->query($total_posts_query);
$total_posts = $total_posts_result->fetchColumn();

// Fetch posts for the current page
$query = "SELECT posts.id, posts.title, posts.thumbnail, users.displayname AS author, posts.created_at 
          FROM posts
          JOIN users ON posts.user_id = users.id
          ORDER BY posts.id DESC
          LIMIT $posts_per_page OFFSET $offset";
$posts = $pdo->query($query);
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
                </select>
            </form>
            <ul class="archive-list">
                <?php
                while ($post = $posts->fetch(PDO::FETCH_ASSOC)) {
                    echo '<li class="archive-item">';
                    if ($post['thumbnail']) {
                        // Adjust the path if necessary
                        $thumbnailPath = str_replace('../', '/', $post['thumbnail']);
                        echo '<img src="' . htmlspecialchars($thumbnailPath) . '" alt="' . htmlspecialchars($post['title']) . ' Thumbnail" class="archive-thumbnail">';
                    }
                    echo '<div class="archive-details">';
                    echo '<a href="post.php?id=' . $post['id'] . '">' . htmlspecialchars($post['title']) . '</a>';
                    echo '<br>';
                    echo 'by ' . htmlspecialchars($post['author']);
                    echo ' on ' . date('F j, Y', strtotime($post['created_at']));
                    echo '</div>';
                    echo '</li>';
                }
                ?>
            </ul>
            <div class="pagination">
                <?php
                $total_pages = ceil($total_posts / $posts_per_page);
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

<!-- Link your updated CSS file -->
<link rel="stylesheet" href="path/to/your/archive.css">

<?php include 'footer.php'; ?>
