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
<style>
  .admin-content { max-width: 1200px; margin: 40px auto; }
  .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 9999; }
  .modal-box { background: #fff; width: 92%; max-width: 900px; padding: 16px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
  .modal-box h3 { margin: 0 0 10px; }
  .modal-box textarea { width: 100%; height: 380px; font-family: monospace; font-size: 14px; }
  .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 10px; }
</style>

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

<script>
// Safer patterns (avoid literal <!-- in JS regex)
const RE_SERVER_PAGEBREAK = new RegExp('<\\!--\\s*pagebreak\\s*-->', 'gi');
const RE_EDITOR_PAGEBREAK = new RegExp('<hr\\b[^>]*class="[^"]*\\bpagebreak\\b[^"]*"[^>]*>', 'gi');

function serverToEditor(html) {
  return (html || '').replace(RE_SERVER_PAGEBREAK, '<hr class="pagebreak">');
}
function editorToServer(html) {
  return (html || '').replace(RE_EDITOR_PAGEBREAK, '<!-- pagebreak -->');
}

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

$(function() {
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
    buttons: { pagebreak: pageBreakButton, rawhtml: rawHtmlButton, plaintext: plainTextButton },
    fontNames: ['Georgia', 'Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Verdana'],
    fontSizes: ['10', '12', '14', '16', '18', '20', '24', '28', '32']
  });

  // On submit: convert HR markers to <!-- pagebreak -->
  const form = document.getElementById('create-post-form');
  if (form) {
    form.addEventListener('submit', function() {
      const html = $('#content').summernote('code');
      document.getElementById('content').value = editorToServer(html);
    });
  }

  // Modal events
  $('#closeRawBtn').on('click', closeRawModal);
  $('#rawModal').on('click', function(e){ if (e.target === this) closeRawModal(); });
  $('#copyRawBtn').on('click', function() {
    const ta = document.getElementById('rawModalTextarea');
    ta.focus(); ta.select(); document.execCommand('copy');
  });
});
</script>
<?php include '../footer.php'; ?>