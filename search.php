<?php
require_once 'includes/database.php';
//require_once 'base_config.php';

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if the request came from the search bar in the header
if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], BASE_URL) !== 0) {
    // Redirect to home page or show an error message
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$searchQuery = '';
$results = [];

if (isset($_GET['query'])) {
    $searchQuery = trim($_GET['query']);
    if (!empty($searchQuery)) {
        // Prepare and execute the search query using PDO
        $stmt = $pdo->prepare("SELECT title, content, created_at FROM posts WHERE title LIKE :query OR content LIKE :query");
        $searchParam = '%' . $searchQuery . '%';
        $stmt->bindParam(':query', $searchParam);
        $stmt->execute();

        // Fetch all results
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<?php include 'header.php'; ?>
<main>
    <div class="search-results">
        <h1>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h1>
        <?php if (empty($results)) : ?>
            <p>No results found.</p>
        <?php else : ?>
            <ul>
                <?php foreach ($results as $result) : ?>
                    <li>
                        <h2><?php echo htmlspecialchars($result['title']); ?></h2>
                        <p><em>Posted on: <?php echo htmlspecialchars($result['created_at']); ?></em></p>
                        <p><?php echo htmlspecialchars(substr($result['content'], 0, 200)) . '...'; ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>
<?php include 'footer.php'; ?>