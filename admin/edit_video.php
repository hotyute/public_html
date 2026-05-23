<?php
require_once __DIR__ . '/../includes/session.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'], true)) {
    header('Location: /login.php');
    exit();
}

$video_file = '../includes/featured_video.txt';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $new_link = trim($_POST['video_link'] ?? '');
    if (!empty($new_link)) {
        file_put_contents($video_file, $new_link);
        $success = 'Video link updated successfully!';
    } else {
        $error = 'Please provide a valid video link.';
    }
}
?>

<?php include '../header.php'; ?>
<h1>Edit Featured Video of the Week</h1>
<?php if ($success) : ?>
    <p style="color: green;"><?php echo $success; ?></p>
<?php endif; ?>
<?php if ($error) : ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>
<form action="" method="POST">
    <label for="video_link">Video Link:</label>
    <input type="text" id="video_link" name="video_link" value="<?php echo htmlspecialchars(file_exists($video_file) ? trim(file_get_contents($video_file)) : '', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit">Update Video</button>
</form>
<?php include '../footer.php'; ?>
