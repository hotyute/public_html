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
                    echo '<h3>' . htmlspecialchars($post['title']) . '</h3>';
                    echo '<p>By ' . htmlspecialchars($post['author']) . '</p>';
                    echo '<div class="content-preview" data-content="' . htmlspecialchars($post['content']) . '"></div>';
                    echo '<p class="comment-count">' . $post['comment_count'] . ' Comments</p>';
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
function getCharacterCount(width, height) {
    // Define ranges for character count adjustment
    const minWidth = 480;
    const maxWidth = 720;
    const minHeight = 500;
    const maxHeight = 1920;

    const minCharsWidth = 30; // Minimum characters at minimum width
    const maxCharsWidth = 75; // Maximum characters at maximum width
    const minCharsHeight = 30; // Minimum characters at minimum height
    const maxCharsHeight = 75; // Maximum characters at maximum height

    // Linear scaling calculations
    const slopeWidth = (maxCharsWidth - minCharsWidth) / (maxWidth - minWidth);
    const slopeHeight = (maxCharsHeight - minCharsHeight) / (maxHeight - minHeight);

    const charLimitWidth = width <= minWidth ? minCharsWidth :
                          width >= maxWidth ? maxCharsWidth :
                          Math.floor(minCharsWidth + slopeWidth * (width - minWidth));

    const charLimitHeight = height <= minHeight ? minCharsHeight :
                           height >= maxHeight ? maxCharsHeight :
                           Math.floor(minCharsHeight + slopeHeight * (height - minHeight));

    // Use the smaller of the two calculated character limits
    return Math.min(charLimitWidth, charLimitHeight);
}

function adjustFontSize(width, height) {
    // Base font size
    const baseFontSize = 14; // Base font size in pixels

    // Scaling factors
    const widthFactor = 0.01;
    const heightFactor = 0.015;

    // Calculate font size based on width and height
    const fontSizeWidth = baseFontSize + (width - 320) * widthFactor;
    const fontSizeHeight = baseFontSize + (height - 480) * heightFactor;

    // Use the smaller of the two to adjust font size
    return Math.min(fontSizeWidth, fontSizeHeight);
}

function adjustContentPreview() {
    const previews = document.querySelectorAll('.content-preview');
    const screenWidth = window.innerWidth;
    const screenHeight = window.innerHeight;
    const charLimit = getCharacterCount(screenWidth, screenHeight);
    const fontSize = adjustFontSize(screenWidth, screenHeight);

    previews.forEach(preview => {
        const fullText = preview.getAttribute('data-content');
        preview.textContent = fullText.length > charLimit ? fullText.substring(0, charLimit) + '...' : fullText;
        preview.style.fontSize = `${fontSize}px`; // Apply the dynamically calculated font size
    });
}

// Adjust content on load and resize
window.addEventListener('load', adjustContentPreview);
window.addEventListener('resize', adjustContentPreview);
</script>
