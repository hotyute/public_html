(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const apiUrl = '/includes/video/api.php';

    function parseVideo(url) {
        try {
            const parsed = new URL(url);
            const host = parsed.hostname.toLowerCase();
            const path = parsed.pathname.replace(/^\/+/, '');

            if (host.includes('youtu.be')) {
                const id = path.split('/')[0];
                return id ? youtubeResult(id) : null;
            }

            if (host.includes('youtube.com') || host.includes('youtube-nocookie.com')) {
                const watchId = parsed.searchParams.get('v');
                if (watchId) return youtubeResult(watchId);

                const match = path.match(/^(embed|shorts|live)\/([A-Za-z0-9_-]{6,})/);
                if (match) return youtubeResult(match[2]);
            }

            if (host.includes('vimeo.com')) {
                const match = path.match(/(\d{6,})/);
                if (match) {
                    return {
                        embed: `https://player.vimeo.com/video/${match[1]}`,
                        preview: '',
                        status: 'Vimeo video ready to embed.'
                    };
                }
            }

            if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
                return { embed: url, preview: '', status: 'Embeddable link ready.' };
            }
        } catch (error) {
            return null;
        }
        return null;
    }

    function youtubeResult(id) {
        return {
            embed: `https://www.youtube.com/embed/${encodeURIComponent(id)}`,
            preview: `https://img.youtube.com/vi/${encodeURIComponent(id)}/hqdefault.jpg`,
            status: 'YouTube video ready to embed.'
        };
    }

    function escapeAttr(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function renderPreview(root, result) {
        const preview = root.querySelector('[data-video-preview]');
        const card = root.querySelector('[data-video-card]');
        const status = root.querySelector('[data-video-status]');
        if (preview) {
            preview.innerHTML = result?.embed
                ? `<iframe src="${escapeAttr(result.embed)}" title="Featured video preview" allowfullscreen></iframe>`
                : '<p>No video selected yet.</p>';
        }
        if (card) {
            const image = result?.preview ? `<img src="${escapeAttr(result.preview)}" alt="Video thumbnail preview">` : '';
            card.innerHTML = `${image}<span data-video-status>${result?.status || 'Paste a supported link to build a preview.'}</span>`;
        } else if (status) {
            status.textContent = result?.status || 'Paste a supported link to build a preview.';
        }
    }

    function wireRoot(root) {
        const input = root.querySelector('[data-video-input]');
        const panel = root.querySelector('[data-video-panel]');
        const editButton = root.querySelector('.js-video-edit');
        const saveButton = root.querySelector('[data-video-save]');
        const cancelButton = root.querySelector('[data-video-cancel]');
        const copyButton = root.querySelector('[data-video-copy-embed]');

        if (input) {
            input.addEventListener('input', () => {
                renderPreview(root, parseVideo(input.value.trim()));
            });
        }

        if (editButton && panel) {
            editButton.addEventListener('click', () => {
                panel.hidden = !panel.hidden;
                if (!panel.hidden) input?.focus();
            });
        }

        if (cancelButton && panel) {
            cancelButton.addEventListener('click', () => {
                panel.hidden = true;
                if (input) input.value = root.dataset.videoUrl || '';
                renderPreview(root, parseVideo(input?.value || ''));
            });
        }

        if (saveButton && input) {
            saveButton.addEventListener('click', () => {
                const result = parseVideo(input.value.trim());
                if (!result) {
                    renderPreview(root, null);
                    return;
                }

                fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ video_link: result.embed })
                })
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) throw new Error(data.message || 'Unable to save video');
                        return data;
                    })
                    .then(data => {
                        root.dataset.videoUrl = data.url || result.embed;
                        input.value = root.dataset.videoUrl;
                        renderPreview(root, parseVideo(root.dataset.videoUrl));
                        if (panel) panel.hidden = true;
                    })
                    .catch(error => {
                        const status = root.querySelector('[data-video-status]');
                        if (status) status.textContent = error.message;
                    });
            });
        }

        if (copyButton) {
            copyButton.addEventListener('click', () => {
                const result = parseVideo(input?.value || root.dataset.videoUrl || '');
                if (result?.embed && navigator.clipboard) {
                    navigator.clipboard.writeText(result.embed);
                    copyButton.textContent = 'Copied';
                    window.setTimeout(() => { copyButton.textContent = 'Copy embed link'; }, 1200);
                }
            });
        }

        if (input?.value) {
            renderPreview(root, parseVideo(input.value.trim()));
        }
    }

    document.querySelectorAll('[data-video-editor]').forEach(wireRoot);
})();
