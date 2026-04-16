import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

const revealCards = () => {
    const elements = document.querySelectorAll('.js-reveal');
    elements.forEach((element, index) => {
        window.setTimeout(() => {
            element.classList.add('is-visible');
        }, index * 60);
    });
};

const highlightActiveMenu = () => {
    const path = window.location.pathname;
    document.querySelectorAll('[data-nav-link]').forEach((link) => {
        const target = link.getAttribute('href');
        if (target && (path === target || (target !== '/' && path.startsWith(target)))) {
            link.classList.add('is-active');
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    revealCards();
    highlightActiveMenu();
});
