(function () {
    document.querySelectorAll('[data-article-carousel]').forEach(carousel => {
        const track = carousel.querySelector('.article-carousel__track');
        const section = carousel.closest('.continue-reading');
        const prev = section?.querySelector('[data-carousel-prev]');
        const next = section?.querySelector('[data-carousel-next]');
        if (!track || !prev || !next) return;
        const tiles = Array.from(track.querySelectorAll('.article-tile'));
        let currentIndex = 0;
        let transitionTimer = null;
        let isAnimating = false;

        function visibleCount() {
            if (window.matchMedia('(max-width: 820px)').matches) return 1;
            if (window.matchMedia('(max-width: 1180px)').matches) return 3;
            return 4;
        }

        function maxStartIndex() {
            return Math.max(0, tiles.length - visibleCount());
        }

        function gapSize() {
            const styles = window.getComputedStyle(track);
            return parseFloat(styles.columnGap || styles.gap || '0') || 0;
        }

        function cardStep() {
            const firstTile = tiles[0];
            if (!firstTile) return 0;
            return firstTile.getBoundingClientRect().width + gapSize();
        }

        function updateFocusTile() {
            if (!tiles.length) return;
            const focusIndex = Math.min(tiles.length - 1, currentIndex + Math.floor((visibleCount() - 1) / 2));
            tiles.forEach((tile, index) => tile.classList.toggle('is-carousel-focus', index === focusIndex));
        }

        function updateButtons(options = {}) {
            const updateFocus = options.updateFocus !== false;
            const maxIndex = maxStartIndex();
            prev.disabled = currentIndex <= 0;
            next.disabled = currentIndex >= maxIndex;
            if (updateFocus) updateFocusTile();
        }

        function finishTransition() {
            if (!isAnimating) return;
            isAnimating = false;
            window.clearTimeout(transitionTimer);
            updateButtons();
        }

        function render(options = {}) {
            const animate = options.animate !== false;
            currentIndex = Math.max(0, Math.min(maxStartIndex(), currentIndex));
            if (!animate) track.classList.add('is-jump');
            track.style.setProperty('--carousel-offset', `${-(currentIndex * cardStep())}px`);
            if (animate) {
                isAnimating = true;
                updateButtons({ updateFocus: false });
                window.clearTimeout(transitionTimer);
                transitionTimer = window.setTimeout(finishTransition, 650);
            } else {
                isAnimating = false;
                window.clearTimeout(transitionTimer);
                updateButtons();
            }
            if (!animate) {
                window.requestAnimationFrame(() => track.classList.remove('is-jump'));
            }
        }

        function animateButton(button) {
            button.classList.remove('is-pulsing');
            void button.offsetWidth;
            button.classList.add('is-pulsing');
        }

        function go(direction) {
            if (isAnimating) return;
            const nextIndex = Math.max(0, Math.min(maxStartIndex(), currentIndex + (visibleCount() * direction)));
            if (nextIndex === currentIndex) return;
            currentIndex = nextIndex;
            animateButton(direction > 0 ? next : prev);
            render();
        }

        prev.addEventListener('click', () => go(-1));
        next.addEventListener('click', () => go(1));

        window.addEventListener('resize', () => render({ animate: false }));
        track.addEventListener('transitionend', event => {
            if (event.propertyName === 'transform') finishTransition();
        });
        render({ animate: false });
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
