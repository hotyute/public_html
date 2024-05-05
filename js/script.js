document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.querySelector('.hamburger');
    const navUL = document.querySelector('nav ul');

    hamburger.addEventListener('click', function () {
        // Toggle class for sliding animation
        navUL.classList.toggle('open');
    });
});