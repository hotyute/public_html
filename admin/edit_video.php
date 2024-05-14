<?php
// Start the session and check if the user is logged in as an admin
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$video_file = '../includes/featured_video.txt';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_link = trim($_POST['video_link']);
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
    <input type="text" id="video_link" name="video_link" value="<?php echo file_exists($video_file) ? trim(file_get_contents($video_file)) : ''; ?>">
    <button type="submit">Update Video</button>
</form>
<?php include '../footer.php'; ?>