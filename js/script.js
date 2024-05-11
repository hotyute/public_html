document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.querySelector('.hamburger');
    const allNavULs = document.querySelectorAll('nav ul');  // Select all ul elements within nav

    hamburger.addEventListener('click', function () {
        allNavULs.forEach(navUL => {  // Apply changes to each ul element
            if (navUL.classList.contains('open')) {
                navUL.style.maxHeight = "0"; // Collapse the menu
                navUL.classList.remove('open');
            } else {
                navUL.style.maxHeight = "1000px"; // Remove any max-height limit
                const fullHeight = navUL.scrollHeight + "px"; // Calculate full height
                navUL.style.maxHeight = "0"; // Reset to zero before animation
                
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        navUL.style.maxHeight = fullHeight; // Set to full height on the next frame
                        navUL.classList.add('open');
                    });
                });
            }
        });
    });
});
