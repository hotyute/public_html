function autoExpand(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}

document.addEventListener('DOMContentLoaded', function () {
    const commentsSection = document.getElementById('commentsSection');
    const commentForm = document.getElementById('commentForm');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function postForm(url, fields) {
        const body = new URLSearchParams(fields);
        if (!body.has('csrf_token')) {
            body.set('csrf_token', csrfToken);
        }

        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        }).then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Request failed');
            }
            return data;
        });
    }

    function reloadComments() {
        window.location.reload();
    }

    if (commentForm) {
        const submitButton = document.getElementById('submitComment');
        submitButton?.addEventListener('click', function () {
            const textarea = commentForm.querySelector('textarea[name="comment"]');
            const postId = commentForm.querySelector('input[name="post_id"]')?.value;
            const comment = textarea?.value.trim() || '';

            if (!comment) {
                return;
            }

            postForm('/includes/comments/submit_comment.php', {
                post_id: postId,
                comment
            }).then(reloadComments).catch(error => alert(error.message));
        });
    }

    commentsSection?.addEventListener('click', function (event) {
        const replyButton = event.target.closest('.submitReply');
        const editButton = event.target.closest('.editComment');
        const deleteButton = event.target.closest('.deleteComment');

        if (replyButton) {
            const form = replyButton.closest('.reply-form');
            const textarea = form?.querySelector('textarea');
            const postId = form?.querySelector('input[name="post_id"]')?.value;
            const comment = textarea?.value.trim() || '';

            if (!comment) {
                return;
            }

            postForm('/includes/comments/submit_comment.php', {
                post_id: postId,
                parent_id: replyButton.dataset.parentId,
                comment
            }).then(reloadComments).catch(error => alert(error.message));
            return;
        }

        if (editButton) {
            const commentEl = editButton.closest('.comment');
            const contentEl = commentEl?.querySelector('.comment-content');
            const currentText = contentEl?.textContent.trim() || '';
            const nextText = window.prompt('Edit comment', currentText);

            if (nextText === null || nextText.trim() === '') {
                return;
            }

            postForm('/includes/comments/edit_comment.php', {
                comment_id: editButton.dataset.commentId,
                content: nextText.trim()
            }).then(reloadComments).catch(error => alert(error.message));
            return;
        }

        if (deleteButton) {
            if (!window.confirm('Delete this comment?')) {
                return;
            }

            postForm('/includes/comments/delete_comment.php', {
                comment_id: deleteButton.dataset.commentId
            }).then(reloadComments).catch(error => alert(error.message));
        }
    });
});
