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
                $query = "SELECT posts.id, posts.title, posts.thumbnail, posts.content, users.displayname AS author FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.id DESC LIMIT 6";
                $posts = $pdo->query($query);
                while ($post = $posts->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div class="post-preview">';
                    echo '<a href="post.php?id=' . $post['id'] . '" style="text-decoration: none; color: black;">';
                    if ($post['thumbnail']) {
                        echo '<img src="' . $post['thumbnail'] . '" alt="Post thumbnail" class="post-thumbnail">';
                    }
                    echo '<h3>' . htmlspecialchars($post['title']) . '</h3>';
                    echo '<p>By ' . htmlspecialchars($post['author']) . '</p>';
                    echo '<div class="content-preview" data-content="' . htmlspecialchars($post['content']) . '"></div>';
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
<script>
function getWordCount(width) {
    // Define minimum and maximum widths and corresponding word counts
    const minWidth = 320;  // Minimum screen width to consider
    const maxWidth = 1200; // Maximum screen width after which content size stabilizes
    const minWords = 30;   // Minimum words to show at or below minWidth
    const maxWords = 100;  // Maximum words to show at or above maxWidth

    if (width <= minWidth) return minWords;
    if (width >= maxWidth) return maxWords;

    // Calculate slope (m) of the line connecting the points (minWidth, minWords) and (maxWidth, maxWords)
    const slope = (maxWords - minWords) / (maxWidth - minWidth);

    // Apply linear equation y = mx + b, where x is width and b is y-intercept
    return Math.floor(slope * (width - minWidth) + minWords);
}

function adjustContentPreview() {
    const previews = document.querySelectorAll('.content-preview');
    previews.forEach(preview => {
        const fullText = preview.getAttribute('data-content');
        const screenWidth = window.innerWidth;
        const wordLimit = getWordCount(screenWidth);
        const words = fullText.split(/\s+/); // Split by whitespace to get words
        if (words.length > wordLimit) {
            preview.textContent = words.slice(0, wordLimit).join(' ') + '...';
        } else {
            preview.textContent = fullText;
        }
    });
}

// Adjust content on load and resize
window.addEventListener('load', adjustContentPreview);
window.addEventListener('resize', adjustContentPreview);
</script>