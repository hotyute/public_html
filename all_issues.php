<?php include 'header.php'; ?>

<?php
// Include the database connection
require 'includes/database.php';

// Fetch all unique issues from the magazine_articles table
$query = "SELECT DISTINCT issue FROM magazine_articles ORDER BY issue DESC";
$issues = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-container">
    <main>
        <section>
            <h2>All Magazine Issues</h2>
            <hr>
            <div class="issue-list">
                <?php if (count($issues) > 0) : ?>
                    <?php foreach ($issues as $issue) : ?>
                        <div class="issue-item">
                            <h3><?php echo htmlspecialchars($issue['issue']); ?></h3>
                            <a href="issue.php?issue=<?php echo urlencode($issue['issue']); ?>" class="view-articles">View Articles</a>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No issues found.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<?php include 'footer.php'; ?>
