<?php include 'header.php'; ?>

<?php
// Database connection, function, and setup code

// Function to determine the current issue based on the current date
function getCurrentIssue() {
    $month = date('n');  // Current month as a number (1-12)
    $year = date('Y');   // Current year

    switch ($month) {
        case 1:
        case 2:
            return "January-February $year";
        case 3:
        case 4:
            return "March-April $year";
        case 5:
        case 6:
            return "May-June $year";
        case 7:
        case 8:
            return "July-August $year";
        case 9:
        case 10:
            return "September-October $year";
        case 11:
        case 12:
            return "November-December $year";
        default:
            return "Unknown Issue";
    }
}

// Calculate the current issue before starting the main HTML output
$issue = getCurrentIssue();

// Truncate content function for limiting post content preview length
function truncateContent($content, $limit = 100) {
    $content = strip_tags($content); // Remove HTML tags
    return strlen($content) > $limit ? substr($content, 0, $limit) . '...' : $content;
}

// Sidebar links array (if needed)
$sidebarLinks = [
    [
        'url' => '#',
        'text' => 'Link 1',
        'thumbnail' => ''
    ],
    [
        'url' => '#',
        'text' => 'Link 2',
        'thumbnail' => ''
    ],
    [
        'url' => '#',
        'text' => 'Link 3',
        'thumbnail' => ''
    ]
];
?>

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
                    $truncatedContent = truncateContent(htmlspecialchars_decode($post['content']), 100); // Adjust character limit as needed
                    echo '<div class="content-preview" data-content="' . $truncatedContent . '"></div>';
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
        <h3>Magazine</h3>
        <h4><?php echo htmlspecialchars($issue); ?></h4>
        <ul>
            <?php
            // Fetch and display the latest articles for the current issue
            $stmt = $pdo->prepare("SELECT title, author, image_url, article_url FROM magazine_articles WHERE issue = ? ORDER BY id DESC LIMIT 3");
            $stmt->bindParam(':issue', $issue);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($result->num_rows > 0) :
                while ($row = $result->fetch_assoc()) :
            ?>
                    <li>
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="thumbnail">
                        <a href="<?php echo htmlspecialchars($row['article_url']); ?>"><?php echo htmlspecialchars($row['title']); ?></a><br>
                        <small><?php echo htmlspecialchars($row['author']); ?></small>
                    </li>
                <?php
                endwhile;
            else :
                ?>
                <li>No articles available for this issue.</li>
            <?php endif; ?>
        </ul>
        <a href="all_issues.php" class="view-all">VIEW ALL</a>
    </aside>

</div>

<?php include 'footer.php'; ?>
