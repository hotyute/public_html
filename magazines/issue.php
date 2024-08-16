<?php include '../header.php'; ?>

<?php
// Include the database connection
require '../includes/database.php';

// Get the selected issue from the URL
$selected_issue = isset($_GET['issue']) ? urldecode($_GET['issue']) : '';

if ($selected_issue) {
    // Fetch all articles related to the selected issue
    $stmt = $pdo->prepare("SELECT title, author, image_url, article_url FROM magazine_articles WHERE issue = :issue ORDER BY id DESC");
    $stmt->bindParam(':issue', $selected_issue);
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="main-container">
    <main>
        <section>
            <h2>Articles for <?php echo htmlspecialchars($selected_issue); ?></h2>
            <hr>
            <div class="articles-list">
                <?php if ($selected_issue && count($articles) > 0) : ?>
                    <?php foreach ($articles as $article) : ?>
                        <div class="article-item">
                            <?php if ($article['image_url']) : ?>
                                <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="article-thumbnail">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                            <p><strong>Author:</strong> <?php echo htmlspecialchars($article['author']); ?></p>
                            <a href="<?php echo htmlspecialchars($article['article_url']); ?>" class="read-more">Read More</a>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No articles found for this issue.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<?php include '../footer.php'; ?>
