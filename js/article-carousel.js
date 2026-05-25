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
        let animationFrame = 0;
        let isProgrammaticSlide = false;

        function pageSize() {
            return Math.max(track.clientWidth, 280);
        }

        function maxScrollLeft() {
            return Math.max(0, track.scrollWidth - track.clientWidth);
        }

        function easing(t) {
            return 1 - Math.pow(1 - t, 3);
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

        function markGliding(duration = 320) {
            track.classList.add('is-gliding');
            window.clearTimeout(scrollTimer);
            scrollTimer = window.setTimeout(() => track.classList.remove('is-gliding'), duration);
        }

        function finishSlide(target) {
            track.scrollLeft = target;
            animationFrame = 0;
            isProgrammaticSlide = false;
            track.classList.remove('is-sliding-next', 'is-sliding-prev');
            window.clearTimeout(scrollTimer);
            scrollTimer = window.setTimeout(() => track.classList.remove('is-gliding'), 80);
            updateButtons();
        }

        function slideBy(distance) {
            const start = track.scrollLeft;
            const target = Math.max(0, Math.min(maxScrollLeft(), start + distance));
            const duration = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ? 0 : 520;
            const directionClass = distance > 0 ? 'is-sliding-next' : 'is-sliding-prev';

            if (animationFrame) {
                window.cancelAnimationFrame(animationFrame);
            }

            track.classList.remove('is-sliding-next', 'is-sliding-prev');
            track.classList.add('is-gliding', directionClass);
            isProgrammaticSlide = true;
            window.clearTimeout(scrollTimer);

            if (duration === 0 || Math.abs(target - start) < 2) {
                finishSlide(target);
                return;
            }

            const startedAt = performance.now();
            const tick = now => {
                const progress = Math.min(1, (now - startedAt) / duration);
                track.scrollLeft = start + ((target - start) * easing(progress));
                scheduleFocusUpdate();

                if (progress < 1) {
                    animationFrame = window.requestAnimationFrame(tick);
                    return;
                }

                finishSlide(target);
            };

            animationFrame = window.requestAnimationFrame(tick);
        }

        function updateButtons() {
            const maxScroll = maxScrollLeft() - 2;
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
            slideBy(-pageSize());
        });

        next.addEventListener('click', () => {
            animateButton(next);
            slideBy(pageSize());
        });

        track.addEventListener('scroll', () => {
            if (!isProgrammaticSlide) {
                markGliding();
            }
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
