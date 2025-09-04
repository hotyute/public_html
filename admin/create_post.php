<?php
session_start();
require '../includes/database.php';
require '../includes/sanitize.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

$status_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $title   = sanitize_html($_POST['title'] ?? '');
    $raw     = $_POST['content'] ?? '';

    // Convert Summernote pagebreaks to <!-- pagebreak -->
    $raw = preg_replace('/<hr\b[^>]*class="[^"]*\bpagebreak\b[^"]*"[^>]*>/i', '<!-- pagebreak -->', $raw);
    $content = sanitize_html2($raw);

    $user_id = $_SESSION['user_id'];
    $thumbnail = null;

    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['thumbnail']['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed, true)) {
            $dir = "../images/uploads/";
            @mkdir($dir, 0755, true);
            $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $name = uniqid('thumb_', true) . '.' . $ext;
            $path = $dir . $name;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $path)) {
                $thumbnail = $path;
            } else {
                $status_message = "Error: failed to move uploaded file.";
            }
        } else {
            $status_message = "Error: invalid image type.";
        }
    }

    $stmt = $pdo->prepare("INSERT INTO posts (title, content, user_id, thumbnail) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$title, $content, $user_id, $thumbnail])) {
        $status_message = "Post added successfully!";
    } else {
        $status_message = "Failed to add post.";
    }
}

include '../header.php';
?>
<div class="admin-content">
    <h2 style="text-align:center;">Create New Post</h2>
    <?php if (!empty($status_message)): ?>
        <p style="text-align:center;color:<?= strpos($status_message, 'success') !== false ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($status_message) ?>
        </p>
    <?php endif; ?>
    <form class="admin-form" method="POST" action="create_post.php" enctype="multipart/form-data" id="create-post-form">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required><br>
        <label for="content">Content:</label>
        <textarea id="content" name="content" rows="18"></textarea><br>
        <label for="thumbnail">Thumbnail:</label>
        <input type="file" id="thumbnail" name="thumbnail" accept="image/*"><br>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="submit" value="Create Post">
    </form>
</div>

<!-- Summernote Lite (self-hosted) + jQuery (self-hosted with CDN fallback) -->
<link href="/vendor/summernote/summernote-lite.min.css" rel="stylesheet">
<script src="/vendor/jquery/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"><\/script>');</script>
<script src="/vendor/summernote/summernote-lite.min.js"></script>

<script>
function pageBreakButton(context) {
  const ui = $.summernote.ui;
  return ui.button({
    contents: '<span style="font-weight:bold;">PB</span>',
    tooltip: 'Page Break',
    click: function () {
      const hr = document.createElement('hr');
      hr.className = 'pagebreak';
      context.invoke('editor.insertNode', hr);
    }
  }).render();
}

document.addEventListener('DOMContentLoaded', function() {
  $('#content').summernote({
    height: 650,
    placeholder: 'Write your post...',
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
      ['font2', ['fontname', 'fontsize', 'color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['insert', ['link', 'table', 'hr']],
      ['view', ['codeview', 'fullscreen', 'help']],
      ['custom', ['pagebreak']]
    ],
    buttons: { pagebreak: pageBreakButton },
    fontNames: ['Georgia', 'Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Verdana'],
    fontSizes: ['10', '12', '14', '16', '18', '20', '24', '28', '32']
  });

  // Convert HR markers to <!-- pagebreak --> on submit
  const form = document.getElementById('create-post-form');
  if (form) {
    form.addEventListener('submit', function() {
      const html = $('#content').summernote('code');
      document.getElementById('content').value = html.replace(/<hr\b[^>]*class="[^"]*\bpagebreak\b[^"]*"[^>]*>/gi, '<!-- pagebreak -->');
    });
  }
});
</script>
<?php include '../footer.php'; ?>