/*
 * JavaScript commun à tous les thèmes : vanilla, aucune dépendance, aucun build.
 *
 * Chargé en tête de page (sans defer) uniquement pour poser la classe « js » avant le premier
 * rendu : le CSS ne replie le menu mobile que si ce script est là pour le rouvrir.
 * La politique CSP par défaut (config/security.php) interdit les scripts inline, d'où ce fichier.
 */
(function () {
    'use strict';

    document.documentElement.classList.add('js');

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    ready(function () {
        var header = document.querySelector('.site-header');
        var toggle = document.querySelector('[data-nav-toggle]');

        if (header && toggle) {
            var setOpen = function (open) {
                header.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? toggle.getAttribute('data-label-close') : toggle.getAttribute('data-label-open'));
            };

            toggle.addEventListener('click', function () {
                setOpen(!header.classList.contains('is-open'));
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && header.classList.contains('is-open')) {
                    setOpen(false);
                    toggle.focus();
                }
            });

            header.querySelectorAll('.site-nav a').forEach(function (link) {
                link.addEventListener('click', function () { setOpen(false); });
            });
        }

        // Ombre sous l'en-tête dès que la page défile.
        if (header) {
            var onScroll = function () {
                header.classList.toggle('is-scrolled', window.scrollY > 8);
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }

        // Apparition douce des blocs .reveal ; sans IntersectionObserver, ils restent visibles.
        var revealed = document.querySelectorAll('.reveal');

        if (revealed.length && 'IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -8% 0px' });

            revealed.forEach(function (element) { observer.observe(element); });
        } else {
            revealed.forEach(function (element) { element.classList.add('is-visible'); });
        }
    });
})();
