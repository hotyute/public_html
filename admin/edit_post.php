<?php
session_start();

// Auth: admins only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

require_once '../base_config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

// Fetch posts for dropdown
$stmt = $pdo->prepare("SELECT id, title, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as formatted_date FROM posts ORDER BY created_at DESC");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle POST (update/delete)
$status_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    if (isset($_POST['delete']) && isset($_POST['post_id'])) {
        $post_id = (int)$_POST['post_id'];

        // Remove thumbnail if exists
        $existing_thumbnail_stmt = $pdo->prepare("SELECT thumbnail FROM posts WHERE id = ?");
        $existing_thumbnail_stmt->execute([$post_id]);
        $existing_thumbnail = $existing_thumbnail_stmt->fetchColumn();

        $delete_stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        if ($delete_stmt->execute([$post_id])) {
            if ($existing_thumbnail && file_exists($existing_thumbnail)) {
                @unlink($existing_thumbnail);
            }
            $status_message = "Post deleted successfully!";
        } else {
            $status_message = "Failed to delete post.";
        }
    } elseif (isset($_POST['post_id'])) {
        $post_id = (int)$_POST['post_id'];
        $title   = sanitize_html($_POST['title'] ?? '');

        // Convert Summernote pagebreak (<hr class="pagebreak">) to <!-- pagebreak -->
        $rawContent = $_POST['content'] ?? '';
        $rawContent = preg_replace('/<hr\b[^>]*class="[^"]*\bpagebreak\b[^"]*"[^>]*>/i', '<!-- pagebreak -->', $rawContent);

        // Sanitize while preserving formatting; custom filter keeps <!-- pagebreak -->
        $content = sanitize_html2($rawContent);

        // Handle thumbnail (optional)
        $existing_thumbnail_stmt = $pdo->prepare("SELECT thumbnail FROM posts WHERE id = ?");
        $existing_thumbnail_stmt->execute([$post_id]);
        $existing_thumbnail = $existing_thumbnail_stmt->fetchColumn();
        $thumbnail = $existing_thumbnail;

        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['thumbnail']['tmp_name']);
            finfo_close($finfo);

            if (in_array($mime, $allowed, true)) {
                if ($existing_thumbnail && file_exists($existing_thumbnail)) {
                    @unlink($existing_thumbnail);
                }
                $target_directory = "../images/uploads/";
                @mkdir($target_directory, 0755, true);
                $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('thumb_', true) . '.' . $ext;
                $target_file = $target_directory . $new_filename;

                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_file)) {
                    $thumbnail = $target_file;
                } else {
                    $status_message = "Failed to move uploaded thumbnail.";
                }
            } else {
                $status_message = "Invalid image type.";
            }
        }

        $update_stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, thumbnail = ? WHERE id = ?");
        if ($update_stmt->execute([$title, $content, $thumbnail, $post_id])) {
            $status_message = "Post updated successfully!";
        } else {
            $status_message = "Failed to update post.";
        }
    }
}

include '../header.php';
?>
<style>
  .admin-content { max-width: 1200px; margin: 40px auto; }
  .admin-content .form-group label { font-weight: bold; display: block; margin: 8px 0; }
  #current_thumbnail img { max-width: 240px; border-radius: 4px; border: 1px solid #ddd; }
  /* Keep editor interactive above surrounding UI */
  .note-editor.note-frame { z-index: 10; position: relative; }
</style>

<!-- Summernote Lite (self-hosted) + jQuery (self-hosted with CDN fallback) -->
<link href="/vendor/summernote/summernote-lite.min.css" rel="stylesheet">
<script src="/vendor/jquery/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"><\/script>');</script>
<script src="/vendor/summernote/summernote-lite.min.js"></script>

<div class="admin-content">
    <h2>Edit Post</h2>
    <?php if (!empty($status_message)): ?>
        <p style="color: <?= strpos($status_message, 'successfully') !== false ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($status_message) ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="edit_post.php" enctype="multipart/form-data" class="admin-form" id="edit-post-form">
        <div class="form-group">
            <label for="post_id">Choose a post to edit:</label>
            <select id="post_id" name="post_id" onchange="loadPostData(this.value)" class="form-control">
                <option value="">Select a post</option>
                <?php foreach ($posts as $post): ?>
                    <option value="<?= (int)$post['id'] ?>"><?= htmlspecialchars($post['title']) ?> - <?= $post['formatted_date'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required class="form-control">
        </div>

        <div class="form-group">
            <label for="content">Content (Bold/Italic/Underline, Colors/Highlight, Fonts, Size, Alignment, Lists, Tables, Links, Page Breaks, Fullscreen):</label>
            <textarea id="content" name="content" rows="18"></textarea>
        </div>

        <div class="form-group">
            <label for="current_thumbnail">Current Thumbnail:</label>
            <div id="current_thumbnail">No thumbnail.</div>
        </div>

        <div class="form-group">
            <label for="thumbnail">Thumbnail (optional):</label>
            <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept="image/*">
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <button type="submit" class="btn btn-primary">Update Post</button>
        <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this post?');">Delete Post</button>
    </form>
</div>

<script>
// Convert between server comments and editor HR markers for page breaks
function serverToEditor(html) {
  return (html || '').replace(/<!--\s*pagebreak\s*-->/gi, '<hr class="pagebreak">');
}
function editorToServer(html) {
  return (html || '').replace(/<hr\b[^>]*class="[^"]*\bpagebreak\b[^"]*"[^>]*>/gi, '<!-- pagebreak -->');
}

// Custom Page Break button (PB)
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

// Raw HTML viewer button (opens modal)
function rawHtmlButton(context) {
  const ui = $.summernote.ui;
  return ui.button({
    contents: '<span style="font-weight:bold;">Raw</span>',
    tooltip: 'View Raw HTML',
    click: function () {
      const html = $('#content').summernote('code');
      openRawModal('Raw HTML (read-only)', html);
    }
  }).render();
}

// Plain text viewer button (opens modal)
function plainTextButton(context) {
  const ui = $.summernote.ui;
  return ui.button({
    contents: '<span style="font-weight:bold;">TXT</span>',
    tooltip: 'View Plain Text',
    click: function () {
      const html = $('#content').summernote('code');
      const text = htmlToPlainText(html);
      openRawModal('Plain Text (read-only)', text);
    }
  }).render();
}

function htmlToPlainText(html) {
  const div = document.createElement('div');
  div.innerHTML = html || '';
  // Keep pagebreaks visible in text preview
  div.querySelectorAll('hr.pagebreak').forEach(hr => {
    hr.replaceWith(document.createTextNode('\n[PAGEBREAK]\n'));
  });
  return div.textContent || div.innerText || '';
}

// Modal helpers
function openRawModal(title, content) {
  $('#rawModalTitle').text(title);
  $('#rawModalTextarea').val(content);
  $('#rawModal').css('display', 'flex').attr('aria-hidden', 'false');
}
function closeRawModal() {
  $('#rawModal').css('display', 'none').attr('aria-hidden', 'true');
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
      ['raw', ['rawhtml', 'plaintext']],
      ['custom', ['pagebreak']]
    ],
    buttons: {
      pagebreak: pageBreakButton,
      rawhtml: rawHtmlButton,
      plaintext: plainTextButton
    },
    fontNames: ['Georgia', 'Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Verdana'],
    fontSizes: ['10', '12', '14', '16', '18', '20', '24', '28', '32'],
    callbacks: {
      onInit: function() {
        $('.note-editable').attr('contenteditable', 'true');
      }
    }
  });

  // Submit: convert HR markers to <!-- pagebreak -->
  const form = document.getElementById('edit-post-form');
  if (form) {
    form.addEventListener('submit', function() {
      const html = $('#content').summernote('code');
      document.getElementById('content').value = editorToServer(html);
    });
  }

  // Modal wiring
  $('#closeRawBtn').on('click', closeRawModal);
  $('#rawModal').on('click', function(e){
    if (e.target === this) closeRawModal();
  });
  $('#copyRawBtn').on('click', function() {
    const ta = document.getElementById('rawModalTextarea');
    ta.select(); ta.setSelectionRange(0, 999999);
    document.execCommand('copy');
  });
});
</script>

<!-- Raw viewer modal -->
<div id="rawModal" class="modal-backdrop" aria-hidden="true">
  <div class="modal-box">
    <h3 id="rawModalTitle">Raw Content</h3>
    <textarea id="rawModalTextarea" readonly></textarea>
    <div class="modal-actions">
      <button type="button" id="copyRawBtn">Copy</button>
      <button type="button" id="closeRawBtn">Close</button>
    </div>
  </div>
</div>
<?php include '../footer.php'; ?>