<?php
// admin/manage_magazines.php
require_once '../includes/session.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}
require_once '../base_config.php';
require_once '../includes/database.php';

function current_issue_label(): string {
    $month = (int)date('n');
    $year  = date('Y');
    switch ($month) {
        case 1: case 2:   $label = "January-February $year"; break;
        case 3: case 4:   $label = "March-April $year"; break;
        case 5: case 6:   $label = "May-June $year"; break;
        case 7: case 8:   $label = "July-August $year"; break;
        case 9: case 10:  $label = "September-October $year"; break;
        case 11: case 12: $label = "November-December $year"; break;
        default:          $label = "Unknown Issue";
    }
    return $label;
}

$today = date('Y-m-d');
$default_issue = current_issue_label();

include '../header.php';
?>
<div class="admin-container">
    <h1>Manage External Magazines</h1>

    <div class="admin-content card" style="max-width: 1200px;">
        <div style="display:flex; flex-wrap: wrap; gap:12px; align-items: center; justify-content: space-between;">
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <label for="filterIssue"><strong>Filter by Issue:</strong></label>
                <select id="filterIssue">
                    <option value="">All Issues</option>
                </select>

                <label for="searchInput"><strong>Search:</strong></label>
                <input id="searchInput" type="text" placeholder="Search title or author" style="min-width:220px;">
                <button id="refreshBtn" type="button">Refresh</button>
            </div>

            <div>
                <button id="newBtn" type="button">+ New Article</button>
            </div>
        </div>

        <hr>

        <!-- Editor Form -->
        <form id="articleForm" class="admin-form" style="display:none; margin-top: 10px;">
            <input type="hidden" id="articleId">
            <div style="display:grid; grid-template-columns: repeat(2, minmax(240px, 1fr)); gap:14px;">
                <div>
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" maxlength="255" required>
                </div>
                <div>
                    <label for="author">Author</label>
                    <input id="author" name="author" type="text" maxlength="255" required>
                </div>
                <div>
                    <label for="image_url">Image URL</label>
                    <input id="image_url" name="image_url" type="url" placeholder="https://..." maxlength="255" required>
                </div>
                <div>
                    <label for="article_url">Article URL</label>
                    <input id="article_url" name="article_url" type="url" placeholder="https://..." maxlength="255" required>
                </div>
                <div>
                    <label for="published_date">Published Date</label>
                    <input id="published_date" name="published_date" type="date" value="<?= htmlspecialchars($today) ?>" required>
                </div>
                <div>
                    <label for="issue">Issue</label>
                    <input list="issueOptions" id="issue" name="issue" value="<?= htmlspecialchars($default_issue) ?>" required>
                    <datalist id="issueOptions"></datalist>
                </div>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:10px;">
                <button type="button" id="cancelEdit">Cancel</button>
                <button type="submit" id="saveBtn">Save</button>
            </div>
        </form>

        <div id="statusMsg" style="margin:10px 0; display:none;"></div>

        <!-- List -->
        <div id="articlesList" style="margin-top: 10px;"></div>

        <div id="pager" style="display:flex; gap:8px; justify-content:center; margin-top:12px;"></div>
    </div>
</div>

<script>
(function(){
  const API = '/includes/magazines/api.php';
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const els = {
    filterIssue: document.getElementById('filterIssue'),
    searchInput: document.getElementById('searchInput'),
    refreshBtn: document.getElementById('refreshBtn'),
    newBtn: document.getElementById('newBtn'),
    form: document.getElementById('articleForm'),
    id: document.getElementById('articleId'),
    title: document.getElementById('title'),
    author: document.getElementById('author'),
    image_url: document.getElementById('image_url'),
    article_url: document.getElementById('article_url'),
    published_date: document.getElementById('published_date'),
    issue: document.getElementById('issue'),
    issueOptions: document.getElementById('issueOptions'),
    cancelEdit: document.getElementById('cancelEdit'),
    saveBtn: document.getElementById('saveBtn'),
    list: document.getElementById('articlesList'),
    msg: document.getElementById('statusMsg'),
    pager: document.getElementById('pager')
  };

  // Pagination state
  let page = 1, perPage = 12, total = 0;

  function showMsg(text, ok=true) {
    els.msg.style.display = 'block';
    els.msg.style.color = ok ? 'green' : 'red';
    els.msg.textContent = text;
    setTimeout(() => { els.msg.style.display = 'none'; }, 3500);
  }

  function computeIssueLabel(d) {
    const month = d.getMonth() + 1;
    const year = d.getFullYear();
    if (month <= 2) return `January-February ${year}`;
    if (month <= 4) return `March-April ${year}`;
    if (month <= 6) return `May-June ${year}`;
    if (month <= 8) return `July-August ${year}`;
    if (month <= 10) return `September-October ${year}`;
    return `November-December ${year}`;
  }

  function populateIssueDatalist() {
    // Suggest current cycle plus neighbors for current & previous year
    els.issueOptions.innerHTML = '';
    const base = new Date();
    const years = [base.getFullYear() - 1, base.getFullYear()];
    const cycles = [
      'January-February', 'March-April', 'May-June',
      'July-August', 'September-October', 'November-December'
    ];
    years.forEach(y => {
      cycles.forEach(c => {
        const opt = document.createElement('option');
        opt.value = `${c} ${y}`;
        els.issueOptions.appendChild(opt);
      });
    });
  }

  function resetForm(setDefaults=true) {
    els.id.value = '';
    els.title.value = '';
    els.author.value = '';
    els.image_url.value = '';
    els.article_url.value = '';
    if (setDefaults) {
      const now = new Date();
      els.published_date.value = now.toISOString().slice(0,10);
      els.issue.value = computeIssueLabel(now);
    }
    els.saveBtn.textContent = 'Save';
  }

  function openForm(edit=false) {
    els.form.style.display = 'block';
    els.saveBtn.textContent = edit ? 'Update' : 'Save';
  }
  function closeForm() {
    els.form.style.display = 'none';
  }

  function fetchJSON(url, opts={}) {
    return fetch(url, opts).then(r => {
      if (!r.ok) throw new Error('Network error');
      return r.json();
    });
  }

  function renderList(items) {
    if (!items.length) {
      els.list.innerHTML = '<p>No articles found.</p>';
      return;
    }
    const wrap = document.createElement('div');
    wrap.style.display = 'grid';
    wrap.style.gridTemplateColumns = 'repeat(auto-fit, minmax(280px, 1fr))';
    wrap.style.gap = '14px';

    items.forEach(a => {
      const card = document.createElement('div');
      card.className = 'card';
      card.style.padding = '10px';

      const row = document.createElement('div');
      row.style.display = 'grid';
      row.style.gridTemplateColumns = '96px 1fr';
      row.style.gap = '10px';

      const img = document.createElement('img');
      img.src = a.image_url;
      img.alt = a.title;
      img.style.width = '96px';
      img.style.height = '96px';
      img.style.objectFit = 'cover';
      img.style.borderRadius = '10px';
      img.referrerPolicy = 'no-referrer';

      const meta = document.createElement('div');
      const title = document.createElement('div');
      title.innerHTML = `<strong>${escapeHtml(a.title)}</strong>`;
      const small = document.createElement('div');
      small.style.fontSize = '0.9em';
      small.style.color = '#555';
      small.textContent = `${a.author} — ${a.published_date}`;
      const issue = document.createElement('div');
      issue.style.fontSize = '0.9em';
      issue.style.color = '#333';
      issue.textContent = a.issue;

      const actions = document.createElement('div');
      actions.style.display = 'flex';
      actions.style.gap = '8px';
      actions.style.marginTop = '8px';

      const editBtn = document.createElement('button');
      editBtn.type = 'button'; editBtn.textContent = 'Edit';
      editBtn.addEventListener('click', () => {
        els.id.value = a.id;
        els.title.value = a.title;
        els.author.value = a.author;
        els.image_url.value = a.image_url;
        els.article_url.value = a.article_url;
        els.published_date.value = a.published_date;
        els.issue.value = a.issue;
        openForm(true);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });

      const delBtn = document.createElement('button');
      delBtn.type = 'button'; delBtn.textContent = 'Delete';
      delBtn.style.backgroundColor = '#dc3545';
      delBtn.addEventListener('click', () => {
        if (!confirm('Delete this article?')) return;
        fetchJSON(API, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
          body: JSON.stringify({ action: 'delete', id: a.id })
        })
        .then(res => {
          if (!res.success) throw new Error(res.message || 'Delete failed');
          showMsg('Deleted.');
          loadList();
        })
        .catch(e => showMsg(e.message, false));
      });

      actions.appendChild(editBtn);
      actions.appendChild(delBtn);

      meta.appendChild(title);
      meta.appendChild(small);
      meta.appendChild(issue);
      meta.appendChild(actions);

      row.appendChild(img);
      row.appendChild(meta);
      card.appendChild(row);
      wrap.appendChild(card);
    });

    els.list.innerHTML = '';
    els.list.appendChild(wrap);
  }

  function renderPager() {
    els.pager.innerHTML = '';
    const pages = Math.max(1, Math.ceil(total / perPage));
    if (pages <= 1) return;
    for (let i = 1; i <= pages; i++) {
      const b = document.createElement('button');
      b.textContent = i;
      if (i === page) b.style.backgroundColor = '#0e3a5d';
      b.addEventListener('click', () => { page = i; loadList(); });
      els.pager.appendChild(b);
    }
  }

  function loadIssues() {
    fetchJSON(`${API}?action=issues`)
      .then(res => {
        const sel = els.filterIssue;
        // keep current selection
        const current = sel.value;
        sel.innerHTML = '<option value="">All Issues</option>';
        (res.issues || []).forEach(iss => {
          const opt = document.createElement('option');
          opt.value = iss; opt.textContent = iss;
          sel.appendChild(opt);
        });
        sel.value = current || '';
      })
      .catch(()=>{});
  }

  function loadList() {
    const params = new URLSearchParams({
      action: 'list',
      page: String(page),
      perPage: String(perPage),
      search: els.searchInput.value.trim(),
      issue: els.filterIssue.value
    });
    fetchJSON(`${API}?${params}`)
      .then(res => {
        total = res.total || 0;
        renderList(res.items || []);
        renderPager();
      })
      .catch(e => showMsg(e.message || 'Load failed', false));
  }

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

  // Handlers
  els.refreshBtn.addEventListener('click', () => { page = 1; loadIssues(); loadList(); });
  els.filterIssue.addEventListener('change', () => { page = 1; loadList(); });

  let t = null;
  els.searchInput.addEventListener('input', () => {
    clearTimeout(t); t = setTimeout(()=>{ page = 1; loadList(); }, 250);
  });

  els.newBtn.addEventListener('click', () => { resetForm(true); openForm(false); window.scrollTo({ top: 0, behavior: 'smooth' }); });
  els.cancelEdit.addEventListener('click', () => { closeForm(); });

  els.form.addEventListener('submit', (e) => {
    e.preventDefault();
    const payload = {
      action: els.id.value ? 'update' : 'create',
      id: els.id.value ? Number(els.id.value) : undefined,
      title: els.title.value.trim(),
      author: els.author.value.trim(),
      image_url: els.image_url.value.trim(),
      article_url: els.article_url.value.trim(),
      published_date: els.published_date.value,
      issue: els.issue.value.trim()
    };
    fetchJSON(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
      body: JSON.stringify(payload)
    })
    .then(res => {
      if (!res.success) throw new Error(res.message || 'Save failed');
      showMsg(els.id.value ? 'Updated.' : 'Created.');
      closeForm(); resetForm(true); loadIssues(); loadList();
    })
    .catch(e => showMsg(e.message, false));
  });

  // Init
  populateIssueDatalist();
  resetForm(true);
  loadIssues();
  loadList();
})();
</script>

<?php include '../footer.php'; ?>