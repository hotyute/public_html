(function () {
    document.querySelectorAll('[data-article-carousel]').forEach(carousel => {
        const track = carousel.querySelector('.article-carousel__track');
        const section = carousel.closest('.continue-reading');
        const prev = section?.querySelector('[data-carousel-prev]');
        const next = section?.querySelector('[data-carousel-next]');
        if (!track || !prev || !next) return;
        const tiles = Array.from(track.querySelectorAll('.article-tile'));
        let scrollTimer = null;
        let frameRequested = false;

        function pageSize() {
            return Math.max(track.clientWidth, 280);
        }

        function updateFocusTile() {
            if (!tiles.length) return;

            const viewportCenter = track.scrollLeft + (track.clientWidth / 2);
            let activeTile = tiles[0];
            let activeDistance = Infinity;

            tiles.forEach(tile => {
                const tileCenter = tile.offsetLeft + (tile.offsetWidth / 2);
                const distance = Math.abs(tileCenter - viewportCenter);
                if (distance < activeDistance) {
                    activeDistance = distance;
                    activeTile = tile;
                }
            });

            tiles.forEach(tile => tile.classList.toggle('is-carousel-focus', tile === activeTile));
        }

        function scheduleFocusUpdate() {
            if (frameRequested) return;
            frameRequested = true;
            window.requestAnimationFrame(() => {
                updateFocusTile();
                frameRequested = false;
            });
        }

        function markGliding() {
            track.classList.add('is-gliding');
            window.clearTimeout(scrollTimer);
            scrollTimer = window.setTimeout(() => track.classList.remove('is-gliding'), 260);
        }

        function updateButtons() {
            const maxScroll = track.scrollWidth - track.clientWidth - 2;
            prev.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= maxScroll;
            scheduleFocusUpdate();
        }

        function animateButton(button) {
            button.classList.remove('is-pulsing');
            void button.offsetWidth;
            button.classList.add('is-pulsing');
        }

        prev.addEventListener('click', () => {
            animateButton(prev);
            markGliding();
            track.scrollBy({ left: -pageSize(), behavior: 'smooth' });
        });

        next.addEventListener('click', () => {
            animateButton(next);
            markGliding();
            track.scrollBy({ left: pageSize(), behavior: 'smooth' });
        });

        track.addEventListener('scroll', () => {
            markGliding();
            updateButtons();
        }, { passive: true });
        window.addEventListener('resize', () => {
            updateButtons();
            scheduleFocusUpdate();
        });
        updateButtons();
        scheduleFocusUpdate();
    });

    document.querySelectorAll('[data-mobile-article-slider]').forEach(slider => {
        const track = slider.querySelector('.article-mobile-list__track');
        const slides = Array.from(slider.querySelectorAll('.article-mobile-slide'));
        const prev = slider.querySelector('[data-mobile-prev]');
        const next = slider.querySelector('[data-mobile-next]');
        const dots = Array.from(slider.querySelectorAll('.article-mobile-list__dots span'));
        if (!track || slides.length <= 1 || !prev || !next) return;

        function currentIndex() {
            if (!slides.length) return 0;
            let closestIndex = 0;
            let closestDistance = Infinity;
            slides.forEach((slide, index) => {
                const distance = Math.abs(slide.offsetLeft - track.scrollLeft);
                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestIndex = index;
                }
            });
            return closestIndex;
        }

        function goTo(index) {
            const safeIndex = Math.max(0, Math.min(slides.length - 1, index));
            track.scrollTo({ left: slides[safeIndex].offsetLeft, behavior: 'smooth' });
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
        window.addEventListener('resize', () => window.requestAnimationFrame(updateMobileState));
        window.addEventListener('orientationchange', () => window.setTimeout(updateMobileState, 180));
        updateMobileState();
        window.requestAnimationFrame(updateMobileState);
    });
})();
