<?php
require_once __DIR__ . '/../includes/session.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'], true)) {
    header('Location: /login.php');
    exit();
}
require_once __DIR__ . '/../includes/content_helpers.php';

$success = '';
$error = '';
$videoData = app_featured_video_data();
$current_video = $videoData['url'];
$current_style = $videoData['container_style'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $new_link = trim($_POST['video_link'] ?? '');
    $style = trim($_POST['container_style'] ?? '');
    try {
        $savedVideo = app_featured_video_save($new_link, $style);
        $current_video = $savedVideo['url'];
        $current_style = $savedVideo['container_style'];
        $success = 'Video link updated successfully!';
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}
$current_embed = app_video_embed_url($current_video);
$current_preview = app_video_preview_image($current_video);
?>

<?php include '../header.php'; ?>
<div class="admin-content video-admin-page" data-video-editor data-video-url="<?= htmlspecialchars($current_video, ENT_QUOTES, 'UTF-8') ?>" data-video-style="<?= htmlspecialchars($current_style, ENT_QUOTES, 'UTF-8') ?>">
    <div class="video-admin-page__header">
        <div>
            <p class="section-kicker">Homepage Feature</p>
            <h1>Edit Video of the Week</h1>
            <p>Paste a YouTube, Vimeo, or embed link and preview exactly what will appear on the homepage.</p>
        </div>
        <a class="button secondary-button" href="/index.php">View Homepage</a>
    </div>

    <?php if ($success) : ?>
        <p class="admin-status is-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if ($error) : ?>
        <p class="admin-status is-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form action="" method="POST" class="admin-form video-admin-form">
        <label for="video_link">Video Link</label>
        <div class="video-admin-form__input">
            <input type="url" id="video_link" name="video_link" data-video-input value="<?php echo htmlspecialchars($current_video, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://www.youtube.com/watch?v=...">
            <input type="hidden" name="container_style" data-video-style-input value="<?= htmlspecialchars($current_style, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Update Video</button>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    </form>

    <div class="video-admin-layout">
        <section class="video-admin-preview">
            <div class="section-heading">
                <div>
                    <p class="section-kicker">Live Preview</p>
                    <h2>Homepage Player</h2>
                </div>
            </div>
            <div class="featured-video__frame" data-video-preview<?= $current_style !== '' ? ' style="' . htmlspecialchars($current_style, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                <?php if ($current_embed !== ''): ?>
                    <iframe src="<?= htmlspecialchars($current_embed, ENT_QUOTES, 'UTF-8') ?>" title="Featured video preview" allowfullscreen></iframe>
                <?php else: ?>
                    <p>No video selected yet.</p>
                <?php endif; ?>
            </div>
            <div class="video-size-tools">
                <label>Container width <input type="range" min="55" max="100" step="1" data-video-width value="100"></label>
                <div class="inline-edit-toolbar__actions">
                    <button type="button" class="secondary-button" data-video-ratio="1.777">Wide</button>
                    <button type="button" class="secondary-button" data-video-ratio="1.333">Classic</button>
                    <button type="button" class="secondary-button" data-video-ratio="1">Square</button>
                    <button type="button" class="secondary-button" data-video-reset-size>Reset size</button>
                </div>
            </div>
        </section>

        <aside class="video-admin-card">
            <p class="section-kicker">Detected Video</p>
            <div class="video-preview-card" data-video-card>
                <?php if ($current_preview !== ''): ?>
                    <img src="<?= htmlspecialchars($current_preview, ENT_QUOTES, 'UTF-8') ?>" alt="Video thumbnail preview">
                <?php endif; ?>
                <span data-video-status><?= $current_embed !== '' ? 'Ready to embed on the homepage.' : 'Paste a supported link to build a preview.' ?></span>
            </div>
            <div class="video-admin-tools">
                <button type="button" class="secondary-button" data-video-copy-embed>Copy embed link</button>
                <?php if ($current_embed !== ''): ?>
                    <a href="<?= htmlspecialchars($current_embed, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open embed</a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
<?php include '../footer.php'; ?>
