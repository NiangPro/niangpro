/*
 * JavaScript de la landing : vanilla, en amélioration progressive.
 * Sans lui, tous les prix mensuels s'affichent, la navigation fonctionne par ancres.
 */
(function () {
    'use strict';

    // Bascule mensuel / annuel : le sélecteur est caché (attribut hidden) tant que ce script n'a pas tourné.
    var billing = document.querySelector('[data-billing]');

    if (billing) {
        billing.hidden = false;

        billing.querySelectorAll('[data-billing-choice]').forEach(function (button) {
            button.addEventListener('click', function () {
                var choice = button.getAttribute('data-billing-choice');

                billing.querySelectorAll('[data-billing-choice]').forEach(function (other) {
                    other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
                });

                document.querySelectorAll('[data-price]').forEach(function (price) {
                    price.hidden = price.getAttribute('data-price') !== choice;
                });
            });
        });
    }

    // Surligne dans la navigation le lien de la section actuellement à l'écran.
    var links = {};

    document.querySelectorAll('.site-nav a[href*="#"]').forEach(function (link) {
        links[link.getAttribute('href').split('#')[1]] = link;
    });

    var sections = Object.keys(links)
        .map(function (id) { return document.getElementById(id); })
        .filter(Boolean);

    if (sections.length && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) { return; }

                Object.keys(links).forEach(function (id) {
                    var current = id === entry.target.id;
                    links[id].classList.toggle('is-current', current);

                    if (current) { links[id].setAttribute('aria-current', 'true'); } else { links[id].removeAttribute('aria-current'); }
                });
            });
        }, { rootMargin: '-45% 0px -50% 0px' });

        sections.forEach(function (section) { observer.observe(section); });
    }
})();
