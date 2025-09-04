<?php
require_once 'includes/database.php';
require_once 'base_config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

$searchQuery = '';
$results = [];

if (isset($_GET['query'])) {
    $searchQuery = trim($_GET['query']);
    if ($searchQuery !== '') {
        $stmt = $pdo->prepare("SELECT id, title, content, created_at FROM posts WHERE title LIKE :query OR content LIKE :query ORDER BY id DESC");
        $searchParam = '%' . $searchQuery . '%';
        $stmt->bindParam(':query', $searchParam);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

include 'header.php';
?>
<div class="main-container">
    <main>
        <div class="search-results">
            <h1>Search Results for "<?= htmlspecialchars($searchQuery) ?>"</h1>
            <?php if (empty($results)) : ?>
                <p>No results found.</p>
            <?php else : ?>
                <ul>
                    <?php foreach ($results as $result) : ?>
                        <li>
                            <h2><a href="/post.php?id=<?= (int)$result['id'] ?>"><?= htmlspecialchars($result['title']) ?></a></h2>
                            <p><em>Posted on: <?= htmlspecialchars($result['created_at']) ?></em></p>
                            <p><?= htmlspecialchars(mb_substr(strip_tags($result['content']), 0, 200)) ?>...</p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include 'footer.php'; ?>