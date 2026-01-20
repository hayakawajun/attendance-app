const hamburger = document.getElementById('js-hamburger');
const navMenu = document.getElementById('js-nav-menu');

hamburger.addEventListener('click', function () {
    hamburger.classList.toggle('is-active');
    navMenu.classList.toggle('is-active');
});

navMenu.addEventListener('click', function (event) {
    if (!event.target.closest('.header__nav-link') && !event.target.closest('.logout__btn')) {
        hamburger.classList.remove('is-active');
        navMenu.classList.remove('is-active');
    }
});