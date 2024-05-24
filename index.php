<?php include 'header.php'; ?>
<div class="main-container">
    <main>
        <section>
            <h2>Welcome to Our Community</h2>
            <p>This is the home of our Christian community where we share insights, teachings, and fellowship together.</p>
            <hr>
            <div class="grid-container">
                <?php
                require 'includes/database.php';
                $query = "SELECT posts.id, posts.title, posts.thumbnail, posts.content, users.displayname AS author, COUNT(comments.id) AS comment_count FROM posts
                      JOIN users ON posts.user_id = users.id
                      LEFT JOIN comments ON posts.id = comments.post_id
                      GROUP BY posts.id
                      ORDER BY posts.id DESC
                      LIMIT 6";
                $posts = $pdo->query($query);
                while ($post = $posts->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div class="post-preview">';
                    echo '<a href="post.php?id=' . $post['id'] . '" style="text-decoration: none; color: black;">';
                    if ($post['thumbnail']) {
                        echo '<img src="' . $post['thumbnail'] . '" alt="Post thumbnail" class="post-thumbnail">';
                    }
                    echo '<h3>' . htmlspecialchars_decode($post['title']) . '</h3>';
                    echo '<p>By ' . htmlspecialchars_decode($post['author']) . '</p>';
                    echo '<div class="content-preview" data-content="' . htmlspecialchars($post['content']) . '"></div>';
                    echo '<p class="comment-count">' . $post['comment_count'] . ' Comments</p>';
                    echo '</a>';
                    echo '</div>';
                }
                ?>
            </div>
            <hr>
            <?php
            // Fetch the video link from a text file (or database)
            $video_link = '';
            $video_file = 'includes/featured_video.txt';
            if (file_exists($video_file)) {
                $video_link = trim(file_get_contents($video_file));
            }
            ?>

            <!-- Featured Video of the Week -->
            <div class="featured-video">
                <h2>Featured Video of the Week</h2>
                <?php if (!empty($video_link)) : ?>
                    <iframe width="560" height="315" src="<?php echo $video_link; ?>" frameborder="0" allowfullscreen></iframe>
                <?php else : ?>
                    <p>No featured video this week. Check back later!</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <aside class="sidebar">
        <h3>Sidebar Content</h3>
        <ul>
            <li><a href="<?php echo BASE_URL; ?>archive.php">All Posts</a></li>
            <li><a href="#">Link 2</a></li>
            <li><a href="#">Link 3</a></li>
        </ul>
    </aside>
</div>

<?php include 'footer.php'; ?>