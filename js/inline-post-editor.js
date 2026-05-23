(function () {
    const apiUrl = '/includes/posts/api.php';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let activeEditor = null;

    function field(name, label, value, tagName) {
        const wrapper = document.createElement('label');
        wrapper.className = 'inline-editor-field';
        wrapper.textContent = label;

        const input = document.createElement(tagName || 'input');
        input.name = name;
        input.value = value || '';
        if (name === 'title') {
            input.maxLength = 255;
            input.required = true;
        }
        if (tagName === 'textarea') {
            input.rows = 10;
            input.required = true;
        }
        wrapper.appendChild(input);
        return wrapper;
    }

    function setMessage(panel, text, isError) {
        const message = panel.querySelector('.inline-editor-message');
        message.textContent = text || '';
        message.classList.toggle('is-error', Boolean(isError));
    }

    function closeEditor() {
        if (!activeEditor) return;
        activeEditor.hiddenNodes.forEach(node => {
            node.hidden = false;
        });
        activeEditor.panel.remove();
        activeEditor.surface.classList.remove('is-inline-editing');
        if (activeEditor.surface.classList.contains('inline-create-surface')) {
            activeEditor.surface.remove();
        }
        activeEditor = null;
    }

    function renderEditor(options) {
        closeEditor();

        const surface = options.surface;
        const post = options.post || { id: '', title: '', thumbnail: '', content: '' };
        const panel = document.createElement('form');
        panel.className = 'inline-editor-panel';
        panel.innerHTML = `
            <div class="inline-editor-panel__top">
                <strong>${options.mode === 'create' ? 'New Article' : 'Editing Article'}</strong>
                <button type="button" class="secondary-button" data-editor-cancel>Cancel</button>
            </div>
            <p class="inline-editor-message" aria-live="polite"></p>
            <div class="inline-editor-panel__actions">
                <button type="submit">Save Article</button>
                ${options.mode === 'update' ? '<a class="button secondary-button" href="/admin/edit_post.php?post_id=' + encodeURIComponent(post.id) + '">Advanced Editor</a>' : ''}
            </div>
        `;

        panel.insertBefore(field('title', 'Title', post.title), panel.querySelector('.inline-editor-message'));
        panel.insertBefore(field('thumbnail', 'Thumbnail URL or path', post.thumbnail), panel.querySelector('.inline-editor-message'));
        panel.insertBefore(field('content', 'Article HTML', post.content, 'textarea'), panel.querySelector('.inline-editor-message'));

        const hiddenNodes = [];
        surface.querySelectorAll('[data-edit-field], #post-content-wrapper').forEach(node => {
            node.hidden = true;
            hiddenNodes.push(node);
        });

        const mount = surface.querySelector('.article-tile__body, .home-hero__content') || surface;
        mount.appendChild(panel);
        surface.classList.add('is-inline-editing');

        activeEditor = { panel, surface, hiddenNodes };
        panel.querySelector('[name="title"]').focus();

        panel.querySelector('[data-editor-cancel]').addEventListener('click', closeEditor);
        panel.addEventListener('submit', event => savePost(event, options.mode, post.id, panel));
    }

    function loadPost(postId, button) {
        if (!postId) return;
        const surface = button.closest('[data-inline-post]') || document.querySelector('.post-container');
        if (!surface) return;

        surface.classList.add('is-loading-editor');
        fetch(`${apiUrl}?action=get&id=${encodeURIComponent(postId)}`)
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to load article');
                }
                return data.post;
            })
            .then(post => {
                renderEditor({ mode: 'update', post, surface });
            })
            .catch(error => {
                alert(error.message);
            })
            .finally(() => surface.classList.remove('is-loading-editor'));
    }

    function openNewPost() {
        const anchor = document.querySelector('[data-inline-create-anchor]') || document.querySelector('main');
        const surface = document.createElement('section');
        surface.className = 'inline-create-surface';
        surface.setAttribute('data-inline-post', '');
        anchor.insertAdjacentElement('afterend', surface);
        renderEditor({ mode: 'create', surface });
    }

    function savePost(event, mode, postId, panel) {
        event.preventDefault();
        const payload = {
            action: mode,
            id: postId ? Number(postId) : undefined,
            title: panel.elements.title.value.trim(),
            thumbnail: panel.elements.thumbnail.value.trim(),
            content: panel.elements.content.value
        };

        setMessage(panel, 'Saving article and preparing reader audio...');
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
                const audioNote = data.audio?.audio_generated
                    ? ' Audio generated.'
                    : ' Reader transcript generated.';
                setMessage(panel, `Saved.${audioNote}`);
                window.setTimeout(() => {
                    if (mode === 'create' && data.id) {
                        window.location.href = `/post.php?id=${encodeURIComponent(data.id)}`;
                    } else {
                        window.location.reload();
                    }
                }, 450);
            })
            .catch(error => setMessage(panel, error.message, true));
    }

    document.addEventListener('click', event => {
        const cancelButton = event.target.closest('[data-editor-cancel]');
        if (cancelButton) return;

        const editButton = event.target.closest('.js-edit-post');
        if (editButton) {
            event.preventDefault();
            loadPost(editButton.dataset.postId, editButton);
            return;
        }

        const newButton = event.target.closest('.js-new-post');
        if (newButton) {
            event.preventDefault();
            openNewPost();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeEditor();
        }
    });
})();
