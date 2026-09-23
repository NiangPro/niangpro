/*
 * JavaScript de l'espace d'administration : vanilla, aucune dépendance, aucun build.
 *
 * Chargé en tête de page (sans defer) pour appliquer le thème choisi avant le premier rendu, sans
 * flash. La CSP par défaut (config/security.php) interdit les scripts inline, d'où ce fichier.
 * Sans JavaScript, tout reste utilisable : le thème suit le système, le menu est ouvert sur grand
 * écran, et les suppressions se font sans confirmation.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'niang-admin-theme';
    var root = document.documentElement;

    function storedTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    var theme = storedTheme();

    if (theme === 'light' || theme === 'dark') {
        root.setAttribute('data-theme', theme);
    }

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    ready(function () {
        // Thème clair / sombre, mémorisé dans ce navigateur.
        var themeToggle = document.querySelector('[data-theme-toggle]');

        if (themeToggle) {
            themeToggle.addEventListener('click', function () {
                var current = root.getAttribute('data-theme')
                    || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                var next = current === 'dark' ? 'light' : 'dark';

                root.setAttribute('data-theme', next);

                try {
                    localStorage.setItem(STORAGE_KEY, next);
                } catch (e) { /* navigation privée : le choix vaut pour la page seulement */ }
            });
        }

        // Barre latérale en tiroir sur petit écran.
        var shell = document.querySelector('.admin-shell');
        var menuToggle = document.querySelector('[data-admin-toggle]');
        var backdrop = document.querySelector('[data-admin-close]');

        if (shell && menuToggle) {
            var setOpen = function (open) {
                shell.classList.toggle('is-open', open);
                menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                menuToggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');

                if (backdrop) {
                    backdrop.hidden = !open;
                }
            };

            menuToggle.addEventListener('click', function () {
                setOpen(!shell.classList.contains('is-open'));
            });

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    setOpen(false);
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && shell.classList.contains('is-open')) {
                    setOpen(false);
                    menuToggle.focus();
                }
            });
        }

        // <form data-confirm="Supprimer ce produit ?"> : confirmation avant envoi.
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        });

        // <select data-autosubmit> : applique un filtre dès qu'il change.
        document.querySelectorAll('[data-autosubmit]').forEach(function (field) {
            field.addEventListener('change', function () {
                if (field.form) {
                    field.form.submit();
                }
            });
        });
    });
})();
