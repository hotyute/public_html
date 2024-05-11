document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.querySelector('.hamburger');
    const navUL = document.querySelector('nav ul');

    hamburger.addEventListener('click', function () {
        if (navUL.classList.contains('open')) {
            // Transition to close the menu
            navUL.style.maxHeight = "0"; // Immediately start collapsing
            navUL.classList.remove('open');
        } else {
            // Transition to open the menu
            // Calculate the full height only once and apply it
            navUL.style.maxHeight = "none"; // Remove any max-height limit
            const fullHeight = navUL.scrollHeight + "px"; // Calculate full height
            navUL.style.maxHeight = "0"; // Reset to zero before animation
            
            // Use requestAnimationFrame to ensure layout has time to reset
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    navUL.style.maxHeight = fullHeight; // Set to full height on the next frame
                    navUL.classList.add('open');
                });
            });
        }
    });
});
