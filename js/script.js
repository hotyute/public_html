(function () {
    function ensureDialog() {
        let dialog = document.querySelector('[data-app-confirm-dialog]');
        if (dialog) return dialog;

        dialog = document.createElement('div');
        dialog.className = 'app-dialog';
        dialog.setAttribute('data-app-confirm-dialog', '');
        dialog.setAttribute('hidden', '');
        dialog.innerHTML = `
            <div class="app-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="app-dialog-title">
                <p class="section-kicker">Please Confirm</p>
                <h2 id="app-dialog-title">Continue?</h2>
                <p data-app-dialog-message></p>
                <div class="app-dialog__actions">
                    <button type="button" class="secondary-button" data-app-dialog-cancel>Cancel</button>
                    <button type="button" data-app-dialog-confirm>Confirm</button>
                </div>
            </div>
        `;
        document.body.appendChild(dialog);
        return dialog;
    }

    window.appConfirmDialog = function (options = {}) {
        return new Promise(resolve => {
            const dialog = ensureDialog();
            const title = dialog.querySelector('#app-dialog-title');
            const message = dialog.querySelector('[data-app-dialog-message]');
            const confirm = dialog.querySelector('[data-app-dialog-confirm]');
            const cancel = dialog.querySelector('[data-app-dialog-cancel]');
            const previousFocus = document.activeElement;

            title.textContent = options.title || 'Continue?';
            message.textContent = options.message || 'Please confirm this action.';
            confirm.textContent = options.confirmText || 'Confirm';
            cancel.textContent = options.cancelText || 'Cancel';

            function close(result) {
                dialog.setAttribute('hidden', '');
                dialog.classList.remove('is-open');
                confirm.removeEventListener('click', confirmHandler);
                cancel.removeEventListener('click', cancelHandler);
                dialog.removeEventListener('click', backdropHandler);
                document.removeEventListener('keydown', keyHandler);
                if (previousFocus && typeof previousFocus.focus === 'function') {
                    previousFocus.focus();
                }
                resolve(result);
            }

            function confirmHandler() {
                close(true);
            }

            function cancelHandler() {
                close(false);
            }

            function backdropHandler(event) {
                if (event.target === dialog) close(false);
            }

            function keyHandler(event) {
                if (event.key === 'Escape') close(false);
            }

            confirm.addEventListener('click', confirmHandler);
            cancel.addEventListener('click', cancelHandler);
            dialog.addEventListener('click', backdropHandler);
            document.addEventListener('keydown', keyHandler);

            dialog.removeAttribute('hidden');
            requestAnimationFrame(() => {
                dialog.classList.add('is-open');
                confirm.focus();
            });
        });
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.querySelector('.hamburger');
    const allNavULs = document.querySelectorAll('nav ul');
    if (!hamburger) return;

    hamburger.addEventListener('click', function () {
        allNavULs.forEach(navUL => {
            if (navUL.classList.contains('open')) {
                navUL.style.maxHeight = "0";
                navUL.classList.remove('open');
            } else {
                navUL.style.maxHeight = "none";
                const fullHeight = navUL.scrollHeight + "px";
                navUL.style.maxHeight = "0";

                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        navUL.style.maxHeight = fullHeight;
                        navUL.classList.add('open');
                    });
                });
            }
        });
    });
});
