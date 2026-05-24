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

    document.querySelectorAll('[data-mobile-article-slider]').forEach(slider => {
        const track = slider.querySelector('.article-mobile-list__track');
        const slides = Array.from(slider.querySelectorAll('.article-mobile-slide'));
        const prev = slider.querySelector('[data-mobile-prev]');
        const next = slider.querySelector('[data-mobile-next]');
        const dots = Array.from(slider.querySelectorAll('.article-mobile-list__dots span'));
        if (!track || slides.length <= 1 || !prev || !next) return;

        function currentIndex() {
            return Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
        }

        function goTo(index) {
            const safeIndex = Math.max(0, Math.min(slides.length - 1, index));
            track.scrollTo({ left: safeIndex * track.clientWidth, behavior: 'smooth' });
        }

        function updateMobileState() {
            const index = currentIndex();
            prev.disabled = index <= 0;
            next.disabled = index >= slides.length - 1;
            dots.forEach((dot, dotIndex) => dot.classList.toggle('is-active', dotIndex === index));
        }

        prev.addEventListener('click', () => goTo(currentIndex() - 1));
        next.addEventListener('click', () => goTo(currentIndex() + 1));
        track.addEventListener('scroll', updateMobileState, { passive: true });
        window.addEventListener('resize', updateMobileState);
        updateMobileState();
    });
})();
