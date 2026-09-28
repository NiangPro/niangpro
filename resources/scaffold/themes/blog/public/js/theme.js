/*
 * Thème « blog » : confort de lecture d'un article. Vanilla, sans dépendance ; chargé avec defer par
 * layouts/base.php. Sans JavaScript, la page reste complète : pas de barre de progression, un sommaire
 * sans surlignage, et un bouton « Copier le lien » inactif.
 */
(function () {
    'use strict';

    var body = document.querySelector('[data-article-body]');

    if (!body) {
        return;
    }

    // Progression de lecture : 0 au début du texte, 1 quand sa fin atteint le bas de l'écran.
    var bar = document.querySelector('[data-reading-progress]');

    if (bar) {
        var ticking = false;
        var update = function () {
            var rect = body.getBoundingClientRect();
            var total = rect.height - window.innerHeight * 0.6;
            var done = Math.min(1, Math.max(0, -rect.top / (total > 0 ? total : 1)));
            bar.style.transform = 'scaleX(' + done.toFixed(4) + ')';
            ticking = false;
        };
        var request = function () {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        };
        window.addEventListener('scroll', request, { passive: true });
        window.addEventListener('resize', request);
        update();
    }

    // Sommaire : surligne la section en cours de lecture.
    var links = Array.prototype.slice.call(document.querySelectorAll('[data-toc-link]'));
    var headings = links.map(function (link) {
        return document.getElementById(decodeURIComponent(link.getAttribute('href').slice(1)));
    });

    if (links.length && 'IntersectionObserver' in window) {
        var activate = function (index) {
            links.forEach(function (link, i) {
                var active = i === index;
                link.classList.toggle('is-active', active);
                if (active) {
                    link.setAttribute('aria-current', 'location');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        };
        var observer = new IntersectionObserver(function () {
            var index = -1;
            headings.forEach(function (heading, i) {
                if (heading && heading.getBoundingClientRect().top < window.innerHeight * 0.35) {
                    index = i;
                }
            });
            activate(index);
        }, { rootMargin: '0px 0px -60% 0px', threshold: [0, 1] });

        headings.forEach(function (heading) {
            if (heading) {
                observer.observe(heading);
            }
        });
        window.addEventListener('scroll', function () {
            var index = -1;
            headings.forEach(function (heading, i) {
                if (heading && heading.getBoundingClientRect().top < window.innerHeight * 0.35) {
                    index = i;
                }
            });
            activate(index);
        }, { passive: true });
    }

    // Copier le lien, avec une annonce pour les lecteurs d'écran.
    var status = document.querySelector('[data-copy-status]');
    var copy = document.querySelector('[data-copy-link]');

    if (copy && navigator.clipboard) {
        copy.addEventListener('click', function () {
            navigator.clipboard.writeText(window.location.href.split('#')[0]).then(function () {
                copy.classList.add('is-done');
                if (status) {
                    status.textContent = 'Lien copié dans le presse-papiers.';
                }
                window.setTimeout(function () {
                    copy.classList.remove('is-done');
                    if (status) {
                        status.textContent = '';
                    }
                }, 2200);
            });
        });
    } else if (copy) {
        copy.hidden = true;
    }

    // Partage natif (mobile, Safari...) : le bouton n'apparaît que si le navigateur le propose.
    var share = document.querySelector('[data-share]');

    if (share && navigator.share) {
        share.hidden = false;
        share.addEventListener('click', function () {
            navigator.share({ title: document.title, url: window.location.href.split('#')[0] }).catch(function () {});
        });
    }
})();
