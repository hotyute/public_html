<?php include 'header.php'; ?>
<div style="display: flex;">
    <div class="main-container">
        <main>
            <section>
                <h2>Welcome to Our Community</h2>
                <p>This is the home of our Christian community where we share insights, teachings, and fellowship together.</p>
                <div class="grid-container"> <!-- Utilizing the grid-container class -->
                    <?php
                    require 'includes/database.php';
                    $query = "SELECT posts.id, posts.title, posts.thumbnail, posts.content, users.displayname AS author FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.id DESC LIMIT 6";
                    $posts = $pdo->query($query);
                    while ($post = $posts->fetch(PDO::FETCH_ASSOC)) {
                        echo '<div class="post-preview">';  // Utilizing the post-preview class
                        echo '<a href="post.php?id=' . $post['id'] . '" style="text-decoration: none; color: black;">';
                        if ($post['thumbnail']) {
                            echo '<img src="' . $post['thumbnail'] . '" alt="Post thumbnail" class="post-thumbnail">';  // Use post-thumbnail class
                        }
                        echo '<h3>' . htmlspecialchars($post['title']) . '</h3>';
                        echo '<p>By ' . htmlspecialchars($post['author']) . '</p>';
                        echo '<div class="content-full">' . substr(htmlspecialchars($post['content']), 0, 100) . '...</div>';  // Use content-full class
                        echo '<div class="content-short">' . substr(htmlspecialchars($post['content']), 0, 50) . '...</div>';  // Use content-short class
                        echo '</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </section>
        </main>
    </div>
    <aside style="flex: 1; background-color: #f0f0f0; padding: 20px;">
        <h3>Sidebar Content</h3>
        <ul>
            <li><a href="#">Link 1</a></li>
            <li><a href="#">Link 2</a></li>
            <li><a href="#">Link 3</a></li>
        </ul>
    </aside>
</div>
<?php include 'footer.php'; ?>