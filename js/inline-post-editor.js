(function () {
    const apiUrl = '/includes/posts/api.php';
    const uploadUrl = '/includes/posts/upload_image.php';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let activeEditor = null;

    function textFromHtml(html) {
        const element = document.createElement('div');
        element.innerHTML = html || '';
        return element.textContent || '';
    }

    function normalizePreviewText(text) {
        return String(text || '').replace(/\s+/g, ' ').trim();
    }

    function textFromEditablePreview(element) {
        const clone = element.cloneNode(true);
        clone.querySelectorAll('.preview-ellipsis').forEach(node => node.remove());
        return normalizePreviewText(textFromHtml(clone.innerHTML));
    }

    function setMessage(text, isError) {
        if (!activeEditor?.message) return;
        activeEditor.message.textContent = text || '';
        activeEditor.message.classList.toggle('is-error', Boolean(isError));
    }

    function audioSaveNote(audio) {
        if (audio?.audio_generated) {
            return ` Audio generated${audio.engine ? ` with ${audio.engine}.` : '.'}`;
        }
        if (audio?.audio_preserved) {
            return ' Existing audio was kept.';
        }
        if (audio?.realistic_audio_requested === false) {
            return ' Realistic audio skipped; browser speech will be used.';
        }
        if (audio?.message) {
            return ` ${audio.message} Browser speech remains as backup.`;
        }
        return ' Reader transcript generated; browser speech remains as backup.';
    }

    function askGenerateRealisticAudio() {
        const options = {
            title: 'Generate Realistic Audio?',
            message: 'This will replace any previously generated article audio. Choose skip to keep existing audio, or use browser speech if no audio exists yet.',
            confirmText: 'Generate Audio',
            cancelText: 'Skip For Now'
        };

        if (typeof window.appConfirmDialog === 'function') {
            return window.appConfirmDialog(options);
        }

        return Promise.resolve(window.confirm(
            'Do you want to generate realistic audio?\n\nThis will delete/remove any previously generated audio.'
        ));
    }

    function makeEditable(element, label) {
        element.contentEditable = 'true';
        element.spellcheck = true;
        element.classList.add('inline-editable-text');
        element.setAttribute('role', 'textbox');
        element.setAttribute('aria-label', label);
    }

    function stopEditingElement(element) {
        if (!element) return;
        element.contentEditable = 'false';
        element.classList.remove('inline-editable-text');
        element.removeAttribute('role');
        element.removeAttribute('aria-label');
    }

    function imageStyleFrom(img) {
        if (!img) return '';
        const pieces = [];
        ['width', 'height', 'maxWidth', 'objectFit', 'objectPosition', 'aspectRatio'].forEach(prop => {
            if (img.style[prop]) {
                const cssProp = prop.replace(/[A-Z]/g, match => `-${match.toLowerCase()}`);
                pieces.push(`${cssProp}: ${img.style[prop]}`);
            }
        });
        return pieces.join('; ');
    }

    function isThumbnailImage(img) {
        if (!activeEditor || !img) return false;
        return img === activeEditor.thumbnailImage;
    }

    function syncImageState(img) {
        if (!activeEditor || !img) return;
        if (isThumbnailImage(img)) {
            activeEditor.thumbnailStyleInput.value = imageStyleFrom(img);
        }
    }

    function splitPages(html) {
        return String(html || '').split(/<!--\s*pagebreak\s*-->/i);
    }

    function mergeVisiblePage(fullHtml, pageNumber, visibleHtml) {
        const pages = splitPages(fullHtml);
        const index = Math.max(0, Math.min(pages.length - 1, Number(pageNumber || 1) - 1));
        pages[index] = visibleHtml;
        return pages.join('<!-- pagebreak -->');
    }

    function findNormalizedTextRange(root, needle) {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        const map = [];
        let flat = '';
        let lastWasSpace = true;

        while (true) {
            const node = walker.nextNode();
            if (!node) break;
            const value = node.nodeValue || '';

            for (let offset = 0; offset < value.length; offset += 1) {
                const char = value[offset];
                if (/\s/.test(char)) {
                    if (!lastWasSpace) {
                        flat += ' ';
                        map.push({ node, offset });
                        lastWasSpace = true;
                    }
                    continue;
                }

                flat += char;
                map.push({ node, offset });
                lastWasSpace = false;
            }
        }

        const matchIndex = flat.indexOf(needle);
        if (matchIndex < 0 || !map[matchIndex] || !map[matchIndex + needle.length - 1]) {
            return null;
        }

        return {
            start: map[matchIndex],
            end: map[matchIndex + needle.length - 1]
        };
    }

    function replaceDomTextRange(root, range, replacementText) {
        const startNode = range.start.node;
        const endNode = range.end.node;
        const startOffset = range.start.offset;
        const endOffset = range.end.offset + 1;

        if (startNode === endNode) {
            startNode.nodeValue = startNode.nodeValue.slice(0, startOffset) +
                replacementText +
                startNode.nodeValue.slice(endOffset);
            return;
        }

        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        const nodes = [];
        let capture = false;

        while (true) {
            const node = walker.nextNode();
            if (!node) break;
            if (node === startNode) capture = true;
            if (capture) nodes.push(node);
            if (node === endNode) break;
        }

        startNode.nodeValue = startNode.nodeValue.slice(0, startOffset) + replacementText;
        nodes.slice(1, -1).forEach(node => {
            node.nodeValue = '';
        });
        endNode.nodeValue = endNode.nodeValue.slice(endOffset);
    }

    function replacePreviewText(fullHtml, originalText, replacementText) {
        const original = normalizePreviewText(originalText);
        const replacement = normalizePreviewText(replacementText);

        if (!original || replacement === original) {
            return { html: fullHtml || '', matched: true, changed: false };
        }

        const host = document.createElement('div');
        host.innerHTML = fullHtml || '';
        const range = findNormalizedTextRange(host, original);
        if (!range) {
            return { html: fullHtml || '', matched: false, changed: false };
        }

        replaceDomTextRange(host, range, replacement);
        return { html: host.innerHTML, matched: true, changed: true };
    }

    function buildContentPayload() {
        if (!activeEditor) return '';
        activeEditor.contentMappingError = false;
        activeEditor.previewContentChanged = false;
        const visibleHtml = cleanEditableHtml(activeEditor.contentEl);
        if (activeEditor.mode === 'create') return visibleHtml;
        if (activeEditor.scope === 'page') {
            return mergeVisiblePage(activeEditor.originalFullContent, activeEditor.pageNumber, visibleHtml);
        }
        if (activeEditor.scope === 'preview') {
            const result = replacePreviewText(
                activeEditor.originalFullContent,
                activeEditor.originalVisibleText,
                textFromHtml(visibleHtml)
            );
            activeEditor.contentMappingError = !result.matched;
            activeEditor.previewContentChanged = result.changed;
            return result.html;
        }
        return visibleHtml;
    }

    function cleanEditableHtml(element) {
        const clone = element.cloneNode(true);
        clone.classList.remove('inline-editable-text');
        clone.removeAttribute('contenteditable');
        clone.removeAttribute('role');
        clone.removeAttribute('aria-label');
        clone.querySelectorAll('.preview-ellipsis').forEach(node => node.remove());
        clone.querySelectorAll('.inline-editable-image, .is-selected-image').forEach(img => {
            img.classList.remove('inline-editable-image', 'is-selected-image');
            img.removeAttribute('contenteditable');
        });
        return clone.innerHTML;
    }

    function closeEditor(restore = true) {
        if (!activeEditor) return;

        activeEditor.surface.classList.remove('is-inline-editing', 'is-loading-editor');
        stopEditingElement(activeEditor.titleEl);
        stopEditingElement(activeEditor.contentEl);
        activeEditor.imageEls.forEach(img => img.classList.remove('inline-editable-image', 'is-selected-image'));
        removeImageHandle();

        if (restore) {
            activeEditor.titleEl.innerHTML = activeEditor.originalTitleHtml;
            activeEditor.contentEl.innerHTML = activeEditor.originalContentHtml;
            if (activeEditor.thumbnailInput) activeEditor.thumbnailInput.value = activeEditor.originalThumbnail || '';
            if (activeEditor.thumbnailImage) {
                activeEditor.thumbnailImage.src = activeEditor.originalThumbnailSrc || activeEditor.thumbnailImage.src;
                activeEditor.thumbnailImage.setAttribute('style', activeEditor.originalThumbnailStyle || '');
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

        const mount = surface.querySelector('.article-tile__body, .home-hero__content, .home-feature-side__content, .article-mobile-row > div') || surface;
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
                <strong>${mode === 'create' ? 'New Article' : 'Editing Visible Article Area'}</strong>
                <span class="inline-editor-message" aria-live="polite"></span>
            </div>
            <label class="inline-thumbnail-field">
                Thumbnail
                <input type="text" name="thumbnail" placeholder="/images/uploads/example.jpg">
                <button type="button" class="secondary-button" data-choose-thumbnail>Choose File</button>
            </label>
            <input type="hidden" name="thumbnail_style">
            <input type="file" data-image-file accept="image/*" hidden>
            <div class="inline-image-tools" data-image-tools hidden>
                <span>Selected image</span>
                <button type="button" class="secondary-button" data-image-ratio="1.777">Wide</button>
                <button type="button" class="secondary-button" data-image-ratio="1">Square</button>
                <button type="button" class="secondary-button" data-image-ratio="0.75">Portrait</button>
                <button type="button" class="secondary-button" data-image-clear-size>Original</button>
            </div>
            <p class="inline-edit-hint">Click a picture to replace it. Drag the corner handle to resize or reshape it.</p>
            <div class="inline-edit-toolbar__actions">
                <button type="button" data-editor-save>Save Article</button>
                <button type="button" class="secondary-button" data-editor-cancel>Cancel</button>
                ${mode === 'update' ? '<a class="button secondary-button" href="/admin/edit_post.php?post_id=' + encodeURIComponent(post.id) + '">Advanced Editor</a>' : ''}
            </div>
        `;
        return toolbar;
    }

    function editorScope(surface, contentEl) {
        if (contentEl.id === 'post-content-wrapper') return 'page';
        if (surface.matches('.home-hero, .home-feature-side, .article-tile, .article-mobile-row')) return 'preview';
        return 'full';
    }

    function prepareImages() {
        if (!activeEditor) return;
        const imageSet = new Set();
        if (activeEditor.thumbnailImage) imageSet.add(activeEditor.thumbnailImage);
        activeEditor.contentEl.querySelectorAll('img').forEach(img => imageSet.add(img));
        activeEditor.imageEls = Array.from(imageSet);
        activeEditor.imageEls.forEach(img => {
            img.classList.add('inline-editable-image');
            img.setAttribute('contenteditable', 'false');
        });
    }

    function renderEditor(options) {
        closeEditor(true);

        const surface = options.surface;
        const post = options.post || { id: '', title: '', thumbnail: '', thumbnail_style: '', content: '' };
        const titleEl = surface.querySelector('[data-edit-field="title"]');
        const contentEl = ensureContentTarget(surface);
        if (!titleEl || !contentEl) return;
        const scope = editorScope(surface, contentEl);
        const originalContentHtml = contentEl.innerHTML;
        const originalVisibleText = scope === 'preview'
            ? (contentEl.dataset.previewText || textFromEditablePreview(contentEl))
            : textFromHtml(contentEl.innerHTML);

        const toolbar = createToolbar(options.mode, post);
        const mount = surface.querySelector('.article-tile__body, .home-hero__content, .home-feature-side__content, .article-mobile-row > div') || surface;
        mount.insertBefore(toolbar, mount.firstChild);

        const thumbnailInput = toolbar.querySelector('[name="thumbnail"]');
        const thumbnailStyleInput = toolbar.querySelector('[name="thumbnail_style"]');
        const fileInput = toolbar.querySelector('[data-image-file]');
        const thumbnailImage = surface.querySelector('.post-thumbnail, [data-edit-image] img');
        thumbnailInput.value = post.thumbnail || '';
        thumbnailStyleInput.value = post.thumbnail_style || imageStyleFrom(thumbnailImage);

        activeEditor = {
            mode: options.mode,
            postId: post.id || '',
            scope,
            pageNumber: surface.dataset.page || 1,
            surface,
            titleEl,
            contentEl,
            toolbar,
            thumbnailInput,
            thumbnailStyleInput,
            fileInput,
            thumbnailImage,
            imageEls: [],
            selectedImage: null,
            message: toolbar.querySelector('.inline-editor-message'),
            originalTitleHtml: titleEl.innerHTML,
            originalContentHtml,
            originalVisibleText,
            originalFullContent: post.content || contentEl.innerHTML,
            originalThumbnail: post.thumbnail || '',
            originalThumbnailSrc: thumbnailImage?.getAttribute('src') || '',
            originalThumbnailStyle: thumbnailImage?.getAttribute('style') || ''
        };

        if (options.mode === 'update') {
            titleEl.textContent = post.title || titleEl.textContent;
            if (thumbnailImage && post.thumbnail) thumbnailImage.src = post.thumbnail;
            if (thumbnailImage && post.thumbnail_style) thumbnailImage.setAttribute('style', post.thumbnail_style);
        }

        if (activeEditor.scope === 'preview') {
            contentEl.textContent = activeEditor.originalVisibleText;
        }

        surface.classList.add('is-inline-editing');
        makeEditable(titleEl, 'Article title');
        makeEditable(contentEl, activeEditor.scope === 'preview' ? 'Visible preview text' : 'Visible article body');
        prepareImages();
        titleEl.focus();

        thumbnailInput.addEventListener('input', () => {
            if (activeEditor?.thumbnailImage && thumbnailInput.value.trim()) {
                activeEditor.thumbnailImage.src = thumbnailInput.value.trim();
            }
        });
        toolbar.querySelector('[data-choose-thumbnail]').addEventListener('click', () => {
            if (activeEditor?.thumbnailImage) {
                chooseImage(activeEditor.thumbnailImage);
            } else {
                chooseImage(null);
            }
        });
        fileInput.addEventListener('change', uploadChosenImage);
        toolbar.querySelector('[data-editor-save]').addEventListener('click', savePost);
        toolbar.querySelector('[data-editor-cancel]').addEventListener('click', () => closeEditor(true));
        toolbar.querySelectorAll('[data-image-ratio]').forEach(button => {
            button.addEventListener('click', () => applyImageRatio(Number(button.dataset.imageRatio)));
        });
        toolbar.querySelector('[data-image-clear-size]').addEventListener('click', clearSelectedImageSize);
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
        syncImageState(activeEditor.selectedImage);
        const content = buildContentPayload();

        if (activeEditor.contentMappingError) {
            setMessage('This preview text could not be safely matched to the full article. No changes were saved. Use the article page or Advanced Editor for this text edit.', true);
            return;
        }

        const payload = {
            action: activeEditor.mode,
            id: activeEditor.postId ? Number(activeEditor.postId) : undefined,
            title: textFromHtml(activeEditor.titleEl.innerHTML).trim(),
            thumbnail: activeEditor.thumbnailInput.value.trim(),
            thumbnail_style: activeEditor.thumbnailStyleInput.value.trim(),
            content,
            content_changed: activeEditor.mode === 'create' || activeEditor.scope !== 'preview' || activeEditor.previewContentChanged
        };

        if (!payload.title || !textFromHtml(payload.content).trim()) {
            setMessage('Title and article body are required.', true);
            return;
        }

        askGenerateRealisticAudio()
            .then(generateAudio => {
                payload.generate_audio = generateAudio;
                setMessage('Saving article and preparing reader audio...');
                return fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Save failed');
                }
                return data;
            })
            .then(data => {
                setMessage(`Saved.${audioSaveNote(data.audio || {})}`);
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

    function chooseImage(img) {
        if (!activeEditor) return;
        activeEditor.pendingImage = img || activeEditor.thumbnailImage || null;
        if (activeEditor.pendingImage) selectImage(activeEditor.pendingImage, false);
        activeEditor.fileInput.value = '';
        activeEditor.fileInput.click();
    }

    function uploadChosenImage() {
        if (!activeEditor || !activeEditor.fileInput.files.length) return;
        const file = activeEditor.fileInput.files[0];
        const image = activeEditor.pendingImage || activeEditor.selectedImage || activeEditor.thumbnailImage;
        const form = new FormData();
        form.append('image', file);

        setMessage('Uploading image...');
        fetch(uploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken },
            body: form
        })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) throw new Error(data.message || 'Image upload failed');
                return data.path;
            })
            .then(path => {
                if (image) {
                    image.src = path;
                    if (isThumbnailImage(image)) activeEditor.thumbnailInput.value = path;
                } else {
                    activeEditor.thumbnailInput.value = path;
                }
                setMessage('Image uploaded.');
            })
            .catch(error => setMessage(error.message, true));
    }

    function selectImage(img, openPicker = true) {
        if (!activeEditor || !img) return;
        if (activeEditor.selectedImage) {
            activeEditor.selectedImage.classList.remove('is-selected-image');
        }
        activeEditor.selectedImage = img;
        img.classList.add('is-selected-image');
        activeEditor.toolbar.querySelector('[data-image-tools]').hidden = false;
        placeImageHandle(img);
        if (openPicker) chooseImage(img);
    }

    function placeImageHandle(img) {
        removeImageHandle();
        if (!activeEditor || !img) return;
        const handle = document.createElement('button');
        handle.type = 'button';
        handle.className = 'inline-image-resize-handle';
        handle.setAttribute('aria-label', 'Resize selected image');
        document.body.appendChild(handle);
        activeEditor.imageHandle = handle;

        const position = () => {
            if (!activeEditor?.imageHandle || !activeEditor.selectedImage) return;
            const rect = activeEditor.selectedImage.getBoundingClientRect();
            activeEditor.imageHandle.style.left = `${window.scrollX + rect.right - 9}px`;
            activeEditor.imageHandle.style.top = `${window.scrollY + rect.bottom - 9}px`;
        };
        activeEditor.positionImageHandle = position;
        position();
        window.addEventListener('scroll', position, true);
        window.addEventListener('resize', position);

        handle.addEventListener('pointerdown', startImageResize);
    }

    function removeImageHandle() {
        if (!activeEditor) return;
        if (activeEditor.positionImageHandle) {
            window.removeEventListener('scroll', activeEditor.positionImageHandle, true);
            window.removeEventListener('resize', activeEditor.positionImageHandle);
        }
        activeEditor.imageHandle?.remove();
        activeEditor.imageHandle = null;
        activeEditor.positionImageHandle = null;
    }

    function startImageResize(event) {
        event.preventDefault();
        if (!activeEditor?.selectedImage) return;
        const img = activeEditor.selectedImage;
        const rect = img.getBoundingClientRect();
        const startX = event.clientX;
        const startY = event.clientY;
        const startWidth = rect.width;
        const startHeight = rect.height;

        function move(moveEvent) {
            const width = Math.max(64, startWidth + moveEvent.clientX - startX);
            const height = Math.max(64, startHeight + moveEvent.clientY - startY);
            img.style.width = `${Math.round(width)}px`;
            img.style.height = `${Math.round(height)}px`;
            img.style.maxWidth = '100%';
            img.style.objectFit = 'cover';
            syncImageState(img);
            activeEditor.positionImageHandle?.();
        }

        function up() {
            document.removeEventListener('pointermove', move);
            document.removeEventListener('pointerup', up);
        }

        document.addEventListener('pointermove', move);
        document.addEventListener('pointerup', up);
    }

    function applyImageRatio(ratio) {
        if (!activeEditor?.selectedImage || !ratio) return;
        const img = activeEditor.selectedImage;
        const width = Math.max(96, img.getBoundingClientRect().width);
        img.style.width = `${Math.round(width)}px`;
        img.style.height = `${Math.round(width / ratio)}px`;
        img.style.maxWidth = '100%';
        img.style.objectFit = 'cover';
        syncImageState(img);
        activeEditor.positionImageHandle?.();
    }

    function clearSelectedImageSize() {
        if (!activeEditor?.selectedImage) return;
        const img = activeEditor.selectedImage;
        img.style.width = '';
        img.style.height = '';
        img.style.objectFit = '';
        img.style.aspectRatio = '';
        syncImageState(img);
        activeEditor.positionImageHandle?.();
    }

    document.addEventListener('click', event => {
        if (activeEditor?.surface.contains(event.target)) {
            const image = event.target.closest('img.inline-editable-image');
            if (image) {
                event.preventDefault();
                selectImage(image);
                return;
            }

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
