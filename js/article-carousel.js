(function () {
    document.querySelectorAll('[data-article-carousel]').forEach(carousel => {
        const track = carousel.querySelector('.article-carousel__track');
        const section = carousel.closest('.continue-reading');
        const prev = section?.querySelector('[data-carousel-prev]');
        const next = section?.querySelector('[data-carousel-next]');
        if (!track || !prev || !next) return;

        function pageSize() {
            return Math.max(track.clientWidth, 280);
        }

        function updateButtons() {
            const maxScroll = track.scrollWidth - track.clientWidth - 2;
            prev.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= maxScroll;
        }

        prev.addEventListener('click', () => {
            track.scrollBy({ left: -pageSize(), behavior: 'smooth' });
        });

        next.addEventListener('click', () => {
            track.scrollBy({ left: pageSize(), behavior: 'smooth' });
        });

        track.addEventListener('scroll', updateButtons, { passive: true });
        window.addEventListener('resize', updateButtons);
        updateButtons();
    });
})();
