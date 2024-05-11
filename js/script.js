document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.querySelector('.hamburger');
    const navUL = document.querySelector('nav ul');

    hamburger.addEventListener('click', function () {
        // Check if the menu is currently open
        if (navUL.classList.contains('open')) {
            // Menu is open, need to close it
            navUL.style.maxHeight = "0px"; // Reset max-height
            navUL.classList.remove('open');
        } else {
            // Menu is closed, need to open it
            // Temporarily set max-height to a very high value to measure full height
            navUL.style.maxHeight = "1000px";
            const fullHeight = navUL.scrollHeight + "px";
            navUL.style.maxHeight = "1000px"; // Reset before animation
            navUL.style.maxWidth = "100%";
            //requestAnimationFrame(() => {
            //    navUL.style.maxHeight = fullHeight; // Set actual needed height
            //    navUL.classList.add('open');
            //});
        }
    });
});
