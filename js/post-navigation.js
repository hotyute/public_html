(function () {
    const navs = document.querySelectorAll('.post-pagination');
    if (!navs.length) return;

    navs.forEach(nav => {
        nav.addEventListener('click', event => {
            const link = event.target.closest('.post-pagination__arrow[href]');
            if (!link) return;

            link.classList.add('is-launching');
            nav.classList.add('is-moving');
        });
    });
})();
