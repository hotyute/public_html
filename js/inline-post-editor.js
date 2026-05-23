(function () {
    const apiUrl = '/includes/posts/api.php';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let modal;
    let form;
    let message;
    let currentMode = 'update';

    function ensureModal() {
        if (modal) return;

        modal = document.createElement('div');
        modal.className = 'inline-editor-modal';
        modal.innerHTML = `
            <div class="inline-editor-dialog" role="dialog" aria-modal="true" aria-labelledby="inlineEditorTitle">
                <div class="inline-editor-header">
                    <h2 id="inlineEditorTitle">Edit Article</h2>
                    <button type="button" class="secondary-button" data-editor-close>Close</button>
                </div>
                <form class="inline-editor-form">
                    <input type="hidden" name="id">
                    <label>
                        Title
                        <input type="text" name="title" maxlength="255" required>
                    </label>
                    <label>
                        Thumbnail URL or path
                        <input type="text" name="thumbnail" placeholder="/images/uploads/example.jpg">
                    </label>
                    <label>
                        Article HTML
                        <textarea name="content" required></textarea>
                    </label>
                    <p class="inline-editor-message" aria-live="polite"></p>
                    <div class="inline-editor-footer">
                        <button type="button" class="secondary-button" data-editor-close>Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        form = modal.querySelector('form');
        message = modal.querySelector('.inline-editor-message');
        modal.querySelectorAll('[data-editor-close]').forEach(button => {
            button.addEventListener('click', closeEditor);
        });
        modal.addEventListener('click', event => {
            if (event.target === modal) closeEditor();
        });
        form.addEventListener('submit', savePost);
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeEditor();
            }
        });
    }

    function setMessage(text, isError) {
        message.textContent = text || '';
        message.classList.toggle('is-error', Boolean(isError));
    }

    function openEditor() {
        ensureModal();
        setMessage('');
        modal.classList.add('is-open');
        form.elements.title.focus();
    }

    function closeEditor() {
        if (modal) modal.classList.remove('is-open');
    }

    function openNewPost() {
        currentMode = 'create';
        ensureModal();
        modal.querySelector('#inlineEditorTitle').textContent = 'New Article';
        form.reset();
        form.elements.id.value = '';
        openEditor();
    }

    function openExistingPost(postId) {
        currentMode = 'update';
        ensureModal();
        modal.querySelector('#inlineEditorTitle').textContent = 'Edit Article';
        form.reset();
        setMessage('Loading...');
        modal.classList.add('is-open');

        fetch(`${apiUrl}?action=get&id=${encodeURIComponent(postId)}`)
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to load article');
                }
                return data.post;
            })
            .then(post => {
                form.elements.id.value = post.id || '';
                form.elements.title.value = post.title || '';
                form.elements.thumbnail.value = post.thumbnail || '';
                form.elements.content.value = post.content || '';
                setMessage('');
                form.elements.title.focus();
            })
            .catch(error => setMessage(error.message, true));
    }

    function savePost(event) {
        event.preventDefault();
        const payload = {
            action: currentMode,
            id: form.elements.id.value ? Number(form.elements.id.value) : undefined,
            title: form.elements.title.value.trim(),
            thumbnail: form.elements.thumbnail.value.trim(),
            content: form.elements.content.value
        };

        setMessage('Saving...');
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Save failed');
                }
                return data;
            })
            .then(data => {
                setMessage('Saved.');
                if (currentMode === 'create' && data.id) {
                    window.location.href = `/post.php?id=${encodeURIComponent(data.id)}`;
                } else {
                    window.location.reload();
                }
            })
            .catch(error => setMessage(error.message, true));
    }

    document.addEventListener('click', event => {
        const editButton = event.target.closest('.js-edit-post');
        if (editButton) {
            event.preventDefault();
            openExistingPost(editButton.dataset.postId);
            return;
        }

        const newButton = event.target.closest('.js-new-post');
        if (newButton) {
            event.preventDefault();
            openNewPost();
        }
    });
})();
