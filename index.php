<?php include 'header.php'; ?>
<div style="display: flex;">
    <main style="flex: 3; padding: 20px;">
        <section>
            <h2>Welcome to Our Community</h2>
            <p>This is the home of our Christian community where we share insights, teachings, and fellowship together.</p>
            <div>
                <?php
                require 'includes/database.php';  // Make sure the database connection is available
                $query = "SELECT id, title, thumbnail, content FROM posts ORDER BY id DESC LIMIT 6";
                $posts = $pdo->query($query);
                while ($post = $posts->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div style="margin-bottom: 20px;">';
                    echo '<a href="post.php?id=' . $post['id'] . '" style="text-decoration: none; color: black;">';
                    if ($post['thumbnail']) {
                        echo '<img src="' . $post['thumbnail'] . '" alt="Post thumbnail" style="width:100%; max-width: 300px; height: auto; display: block;">';
                    }
                    echo '<h3>' . htmlspecialchars($post['title']) . '</h3>';
                    echo '<p>' . substr(htmlspecialchars($post['content']), 0, 100) . '...</p>';
                    echo '</a>';
                    echo '</div>';
                }
                ?>
            </div>
        </section>
    </main>
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
