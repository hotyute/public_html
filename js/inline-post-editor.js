(function () {
    const apiUrl = '/includes/posts/api.php';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let activeEditor = null;

    function textFromHtml(html) {
        const element = document.createElement('div');
        element.innerHTML = html || '';
        return element.textContent || '';
    }

    function setMessage(text, isError) {
        if (!activeEditor?.message) return;
        activeEditor.message.textContent = text || '';
        activeEditor.message.classList.toggle('is-error', Boolean(isError));
    }

    function makeEditable(element, label) {
        element.contentEditable = 'true';
        element.spellcheck = true;
        element.classList.add('inline-editable-text');
        element.setAttribute('role', 'textbox');
        element.setAttribute('aria-label', label);
    }

    function stopEditingElement(element) {
        element.contentEditable = 'false';
        element.classList.remove('inline-editable-text');
        element.removeAttribute('role');
        element.removeAttribute('aria-label');
    }

    function closeEditor(restore = true) {
        if (!activeEditor) return;

        activeEditor.surface.classList.remove('is-inline-editing', 'is-loading-editor');
        stopEditingElement(activeEditor.titleEl);
        stopEditingElement(activeEditor.contentEl);

        if (restore) {
            activeEditor.titleEl.innerHTML = activeEditor.originalTitleHtml;
            activeEditor.contentEl.innerHTML = activeEditor.originalContentHtml;
            if (activeEditor.thumbnailInput) {
                activeEditor.thumbnailInput.value = activeEditor.originalThumbnail || '';
            }
        }

        activeEditor.toolbar.remove();
        if (activeEditor.surface.classList.contains('inline-create-surface')) {
            activeEditor.surface.remove();
        }
        activeEditor = null;
    }

    function createEditableSurface() {
        const anchor = document.querySelector('[data-inline-create-anchor]') || document.querySelector('main');
        const surface = document.createElement('section');
        surface.className = 'inline-create-surface';
        surface.setAttribute('data-inline-post', '');
        surface.innerHTML = `
            <div class="inline-create-shell">
                <h2 data-edit-field="title">New article title</h2>
                <div data-edit-field="content">Start writing the article here.</div>
            </div>
        `;
        anchor.insertAdjacentElement('afterend', surface);
        return surface;
    }

    function ensureContentTarget(surface) {
        let contentEl = surface.querySelector('#post-content-wrapper, [data-edit-field="content"]');
        if (contentEl) return contentEl;

        contentEl = document.createElement('div');
        contentEl.className = 'inline-edit-body';
        contentEl.setAttribute('data-edit-field', 'content');

        const mount = surface.querySelector('.article-tile__body, .home-hero__content') || surface;
        const actions = mount.querySelector('.article-tile__actions, .home-hero__actions');
        if (actions) {
            actions.insertAdjacentElement('beforebegin', contentEl);
        } else {
            mount.appendChild(contentEl);
        }
        return contentEl;
    }

    function createToolbar(mode, post) {
        const toolbar = document.createElement('div');
        toolbar.className = 'inline-edit-toolbar';
        toolbar.innerHTML = `
            <div class="inline-edit-toolbar__row">
                <strong>${mode === 'create' ? 'New Article' : 'Editing Article'}</strong>
                <span class="inline-editor-message" aria-live="polite"></span>
            </div>
            <label class="inline-thumbnail-field">
                Thumbnail
                <input type="text" name="thumbnail" placeholder="/images/uploads/example.jpg">
            </label>
            <div class="inline-edit-toolbar__actions">
                <button type="button" data-editor-save>Save Article</button>
                <button type="button" class="secondary-button" data-editor-cancel>Cancel</button>
                ${mode === 'update' ? '<a class="button secondary-button" href="/admin/edit_post.php?post_id=' + encodeURIComponent(post.id) + '">Advanced Editor</a>' : ''}
            </div>
        `;
        return toolbar;
    }

    function renderEditor(options) {
        closeEditor(true);

        const surface = options.surface;
        const post = options.post || { id: '', title: '', thumbnail: '', content: '' };
        const titleEl = surface.querySelector('[data-edit-field="title"]');
        const contentEl = ensureContentTarget(surface);
        if (!titleEl || !contentEl) return;

        const toolbar = createToolbar(options.mode, post);
        const mount = surface.querySelector('.article-tile__body, .home-hero__content') || surface;
        mount.insertBefore(toolbar, mount.firstChild);

        const thumbnailInput = toolbar.querySelector('[name="thumbnail"]');
        thumbnailInput.value = post.thumbnail || '';

        activeEditor = {
            mode: options.mode,
            postId: post.id || '',
            surface,
            titleEl,
            contentEl,
            toolbar,
            thumbnailInput,
            message: toolbar.querySelector('.inline-editor-message'),
            originalTitleHtml: titleEl.innerHTML,
            originalContentHtml: contentEl.innerHTML,
            originalThumbnail: post.thumbnail || ''
        };

        if (options.mode === 'update') {
            titleEl.textContent = post.title || '';
            contentEl.innerHTML = post.content || '';
        }

        surface.classList.add('is-inline-editing');
        makeEditable(titleEl, 'Article title');
        makeEditable(contentEl, 'Article body');
        titleEl.focus();

        toolbar.querySelector('[data-editor-save]').addEventListener('click', savePost);
        toolbar.querySelector('[data-editor-cancel]').addEventListener('click', () => closeEditor(true));
    }

    function loadPost(postId, button) {
        if (!postId) return;
        const surface = button.closest('[data-inline-post]') || document.querySelector('.post-container');
        if (!surface) return;

        closeEditor(true);
        surface.classList.add('is-loading-editor');
        fetch(`${apiUrl}?action=get&id=${encodeURIComponent(postId)}`)
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to load article');
                }
                return data.post;
            })
            .then(post => renderEditor({ mode: 'update', post, surface }))
            .catch(error => alert(error.message))
            .finally(() => surface.classList.remove('is-loading-editor'));
    }

    function openNewPost() {
        const surface = createEditableSurface();
        renderEditor({ mode: 'create', surface });
    }

    function savePost(event) {
        event.preventDefault();
        if (!activeEditor) return;

        const payload = {
            action: activeEditor.mode,
            id: activeEditor.postId ? Number(activeEditor.postId) : undefined,
            title: textFromHtml(activeEditor.titleEl.innerHTML).trim(),
            thumbnail: activeEditor.thumbnailInput.value.trim(),
            content: activeEditor.contentEl.innerHTML
        };

        if (!payload.title || !payload.content.trim()) {
            setMessage('Title and article body are required.', true);
            return;
        }

        setMessage('Saving article and preparing reader audio...');
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
                setMessage(`Saved.${audioNote}`);
                window.setTimeout(() => {
                    if (activeEditor?.mode === 'create' && data.id) {
                        window.location.href = `/post.php?id=${encodeURIComponent(data.id)}`;
                    } else {
                        window.location.reload();
                    }
                }, 450);
            })
            .catch(error => setMessage(error.message, true));
    }

    document.addEventListener('click', event => {
        if (activeEditor?.surface.contains(event.target)) {
            const editableLink = event.target.closest('a[contenteditable="true"]');
            if (editableLink) event.preventDefault();
        }

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
        if (!activeEditor) return;
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
            event.preventDefault();
            savePost(event);
        }
        if (event.key === 'Escape') {
            closeEditor(true);
        }
    });
})();
