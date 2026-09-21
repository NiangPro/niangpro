/*
 * JavaScript de la boutique : vanilla, en amélioration progressive — sans lui, les formulaires,
 * le tri et la fiche produit fonctionnent quand même (première vue affichée, saisie manuelle des quantités).
 */
(function () {
    'use strict';

    // Boutons − / + des sélecteurs de quantité, bornés par min/max de l'input.
    document.querySelectorAll('[data-qty]').forEach(function (group) {
        var input = group.querySelector('input');

        group.querySelectorAll('[data-qty-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                var min = input.min === '' ? 1 : parseInt(input.min, 10);
                var max = input.max === '' ? 99 : parseInt(input.max, 10);
                var next = (parseInt(input.value, 10) || min) + parseInt(button.getAttribute('data-qty-step'), 10);

                input.value = Math.min(max, Math.max(min, next));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });

    // Galerie de la fiche produit : une vignette affiche sa vue.
    document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
        var slides = gallery.querySelectorAll('[data-slide]');
        var thumbs = gallery.querySelectorAll('[data-thumb]');

        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                var index = thumb.getAttribute('data-thumb');

                slides.forEach(function (slide) { slide.hidden = slide.getAttribute('data-slide') !== index; });
                thumbs.forEach(function (other) { other.setAttribute('aria-pressed', other === thumb ? 'true' : 'false'); });
            });
        });
    });

    // Le tri du catalogue s'applique dès qu'on change de valeur.
    document.querySelectorAll('[data-autosubmit]').forEach(function (select) {
        select.addEventListener('change', function () { select.form.submit(); });
    });
})();
