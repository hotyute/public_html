document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.querySelector('.hamburger');
    const navUL = document.querySelector('nav ul');

    hamburger.addEventListener('click', function () {
        navUL.style.display = navUL.style.display === 'block' ? 'none' : 'block';
    });
});