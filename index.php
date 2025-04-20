<?php include 'header.php'; ?>

<?php
// Database connection, function, and setup code

// Function to determine the current issue based on the current date
function getCurrentIssue()
{
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

function getUserClass($user_role)
{
    switch ($user_role) {
        case 'admin':
        case 'owner':
            return 'admin-owner';
        case 'editor':
            return 'editor-user';
        default:
            return 'regular-user';
    }
}

// Calculate the current issue before starting the main HTML output
$issue = getCurrentIssue();

// Truncate content function for limiting post content preview length
function truncateContent($content, $limit = 100)
{
    $content = strip_tags($content); // Remove HTML tags
    return strlen($content) > $limit ? substr($content, 0, $limit) . '...' : $content;
}
?>

<div class="main-container">
    <main>
        <section>
            <h2>Welcome to the DivineWord Community</h2>
            <p>This is the home of the Christian community, part of the little flock, where we share insights, teachings, and fellowship together.</p>
            <hr>
            <div class="carousel-container">
                <div class="carousel-button-row mobile-only">
                    <button class="carousel-button prev" onclick="prevSlide()" aria-label="Previous slide">
                        <svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">
                            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
                        </svg>
                    </button>
                    <button class="carousel-button next" onclick="nextSlide()" aria-label="Next slide">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z" />
                        </svg>
                    </button>
                    <div class="carousel">
                        <div class="carousel-slides">
                            <?php
                            require 'includes/database.php';
                            // Removed LIMIT clause to fetch all posts
                            $query = "SELECT posts.id, posts.title, posts.thumbnail, posts.content, users.displayname AS author, users.role AS user_role, COUNT(comments.id) AS comment_count FROM posts
                                  JOIN users ON posts.user_id = users.id
                                  LEFT JOIN comments ON posts.id = comments.post_id
                                  GROUP BY posts.id
                                  ORDER BY posts.id DESC";
                            $posts = $pdo->query($query);
                            $count = 0;
                            while ($post = $posts->fetch(PDO::FETCH_ASSOC)) {
                                if ($count % 6 == 0) {
                                    if ($count > 0) {
                                        echo '</div>'; // Close previous slide
                                    }
                                    echo '<div class="carousel-slide grid-container"">'; // Start new slide
                                }
                                $userClass = getUserClass($post['user_role']);
                                echo '<div class="post-preview">';
                                echo '<a href="post.php?id=' . $post['id'] . '" style="text-decoration: none; color: black;">';
                                if ($post['thumbnail']) {
                                    echo '<img src="' . $post['thumbnail'] . '" alt="Post thumbnail" class="post-thumbnail">';
                                }
                                echo '<h3>' . htmlspecialchars_decode($post['title']) . '</h3>';
                                echo '<p>By <span class="' . $userClass . '">' . htmlspecialchars_decode($post['author']) . '</span></p>';
                                $truncatedContent = truncateContent(htmlspecialchars_decode($post['content']), 100);
                                echo '<div class="content-preview" data-content="' . $truncatedContent . '"></div>';
                                echo '<p class="comment-count">' . $post['comment_count'] . ' Comments</p>';
                                echo '</a>';
                                echo '</div>';
                                $count++;
                            }
                            if ($count > 0) {
                                echo '</div>'; // Close last slide
                            }
                            ?>
                        </div>
                    </div>
                    <button class="carousel-button prev" onclick="prevSlide()" aria-label="Previous slide">
                        <svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">
                            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
                        </svg>
                    </button>
                    <button class="carousel-button next" onclick="nextSlide()" aria-label="Next slide">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z" />
                        </svg>
                    </button>
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
        <h3>External Magazines</h3>
        <h4><?php echo htmlspecialchars($issue); ?></h4>
        <ul>
            <?php
            // Fetch and display the latest articles for the current issue
            $stmt = $pdo->prepare("SELECT title, author, image_url, article_url FROM magazine_articles WHERE issue = :issue ORDER BY id DESC LIMIT 3");
            $stmt->bindParam(':issue', $issue);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) > 0) :
                foreach ($results as $row) :
            ?>
                    <li>
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="thumbnail">
                        <a href="<?php echo htmlspecialchars($row['article_url']); ?>"><?php echo htmlspecialchars($row['title']); ?></a><br>
                        <small><?php echo htmlspecialchars($row['author']); ?></small>
                    </li>
                <?php
                endforeach;
            else :
                ?>
                <li>No articles available for this issue.</li>
            <?php endif; ?>
        </ul>
        <a href="magazines/all_issues.php" class="view-all">VIEW ALL</a>
    </aside>
</div>

<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.carousel-slide');

    function showSlide(index) {
        const totalSlides = slides.length;
        if (index < 0) {
            currentSlide = totalSlides - 1;
        } else if (index >= totalSlides) {
            currentSlide = 0;
        } else {
            currentSlide = index;
        }
        const carouselSlides = document.querySelector('.carousel-slides');
        carouselSlides.style.transform = 'translateX(' + (-currentSlide * 100) + '%)';
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
    }

    function prevSlide() {
        showSlide(currentSlide - 1);
    }
</script>

<?php include 'footer.php'; ?>