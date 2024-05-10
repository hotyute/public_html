<?php include 'header.php'; ?>
<div style="display: flex;">
    <main style="flex: 3; padding: 20px;">
        <section>
            <h2>Welcome to Our Community</h2>
            <p>This is the home of our Christian community where we share insights, teachings, and fellowship together.</p>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <?php
                require 'includes/database.php';
                $query = "SELECT posts.id, posts.title, posts.thumbnail, posts.content, users.username AS author FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.id DESC LIMIT 6";
                $posts = $pdo->query($query);
                while ($post = $posts->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div style="background: #fff; border: 1px solid #ccc; padding: 10px;">';
                    echo '<a href="post.php?id=' . $post['id'] . '" style="text-decoration: none; color: black;">';
                    if ($post['thumbnail']) {
                        echo '<img src="' . $post['thumbnail'] . '" alt="Post thumbnail" style="width:100%; height: auto;">';
                    }
                    echo '<h3>' . htmlspecialchars($post['title']) . '</h3>';
                    echo '<p>By ' . htmlspecialchars($post['author']) . '</p>';
                    echo '<div class="content-full">' . substr(htmlspecialchars($post['content']), 0, 100) . '...</div>';
                    echo '<div class="content-short">' . substr(htmlspecialchars($post['content']), 0, 50) . '...</div>';
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
