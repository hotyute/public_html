(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const apiUrl = '/includes/magazines/api.php';
    const root = document.querySelector('[data-magazine-admin]');
    if (!root) return;

    const form = root.querySelector('[data-magazine-form]');
    if (!form) return;

    const message = root.querySelector('[data-magazine-message]');

    function setMessage(text, isError) {
        if (!message) return;
        message.textContent = text || '';
        message.classList.toggle('is-error', Boolean(isError));
    }

    function openForm(values) {
        form.hidden = false;
        form.elements.id.value = values?.id || '';
        form.elements.title.value = values?.title || '';
        form.elements.author.value = values?.author || '';
        form.elements.image_url.value = values?.image_url || '';
        form.elements.article_url.value = values?.article_url || '';
        form.elements.published_date.value = values?.published_date || new Date().toISOString().slice(0, 10);
        form.elements.issue.value = values?.issue || form.elements.issue.defaultValue || '';
        setMessage('');
        form.elements.title.focus();
    }

    function valuesFromCard(card) {
        return {
            id: card.dataset.magazineId || '',
            title: card.dataset.title || '',
            author: card.dataset.author || '',
            image_url: card.dataset.imageUrl || '',
            article_url: card.dataset.articleUrl || '',
            published_date: card.dataset.publishedDate || '',
            issue: card.dataset.issue || ''
        };
    }

    root.addEventListener('click', event => {
        const newButton = event.target.closest('.js-magazine-new');
        if (newButton) {
            event.preventDefault();
            openForm();
            return;
        }

        const editButton = event.target.closest('.js-magazine-edit');
        if (editButton) {
            event.preventDefault();
            const card = editButton.closest('[data-magazine-id]');
            if (card) openForm(valuesFromCard(card));
            return;
        }

        if (event.target.closest('[data-magazine-cancel]')) {
            event.preventDefault();
            form.hidden = true;
            setMessage('');
        }
    });

    form.addEventListener('submit', event => {
        event.preventDefault();
        const id = Number(form.elements.id.value || 0);
        const payload = {
            action: id > 0 ? 'update' : 'create',
            id,
            title: form.elements.title.value.trim(),
            author: form.elements.author.value.trim(),
            image_url: form.elements.image_url.value.trim(),
            article_url: form.elements.article_url.value.trim(),
            published_date: form.elements.published_date.value,
            issue: form.elements.issue.value.trim()
        };

        setMessage('Saving magazine article...');
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
                if (!response.ok || !data.success) throw new Error(data.message || 'Unable to save magazine article');
                return data;
            })
            .then(() => {
                setMessage('Saved. Refreshing preview...');
                window.setTimeout(() => window.location.reload(), 450);
            })
            .catch(error => setMessage(error.message, true));
    });
})();
