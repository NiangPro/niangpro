<?php
/**
 * Contenu interne (formes SVG, grille 24 × 24, trait 2) d'une icône, sans balise <svg> autour :
 * components/icon l'enveloppe dans un <svg> autonome, components/art l'insère dans une illustration.
 * Nom inconnu : ne rend rien. Pour ajouter une icône, ajoutez une ligne à la liste ci-dessous.
 *
 * @var string $name
 */
$paths = [
    'check' => '<path d="M20 6 9 17l-5-5"/>',
    'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
    'arrow-left' => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
    'arrow-up-right' => '<path d="M7 17 17 7"/><path d="M8 7h9v9"/>',
    'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
    'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
    'star' => '<path d="m12 2.5 2.9 6 6.6.9-4.8 4.6 1.2 6.5-5.9-3.1-5.9 3.1 1.2-6.5L2.5 9.4l6.6-.9z"/>',
    'shield' => '<path d="M12 3 4 6v6c0 4.5 3.2 7.8 8 9 4.8-1.2 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
    'zap' => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
    'heart' => '<path d="M12 21s-7-4.6-9.3-9A5.4 5.4 0 0 1 12 6a5.4 5.4 0 0 1 9.3 6c-2.3 4.4-9.3 9-9.3 9z"/>',
    'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14a6.5 6.5 0 0 1 3.5 6"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
    'map-pin' => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'home' => '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
    'layers' => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
    'pen' => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m14 6 4 4"/>',
    'palette' => '<path d="M12 3a9 9 0 1 0 0 18c1.5 0 2-1 1.5-2-.6-1.2.3-2.5 1.7-2.5H17a4 4 0 0 0 4-4C21 6.6 17 3 12 3z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="15" cy="7.5" r="1"/>',
    'tool' => '<path d="M14.5 6.5a4 4 0 0 0 5 5L21 13l-8 8a2.1 2.1 0 0 1-3-3l8-8z"/><path d="M14.5 6.5 17 4l3 3-2.5 2.5"/>',
    'sparkles' => '<path d="m12 3 1.8 4.7 4.7 1.8-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="m19 15 .8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
    'cart' => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M3 4h2.5l2.4 11h10.2l2-8H7"/>',
    'bag' => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8a3 3 0 0 1 6 0"/>',
    'package' => '<path d="m12 3 8.5 4.5v9L12 21l-8.5-4.5v-9z"/><path d="m3.5 7.5 8.5 4.5 8.5-4.5M12 12v9"/>',
    'truck' => '<path d="M2 6h11v10H2z"/><path d="M13 9h4l4 3v4h-8"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
    'refresh' => '<path d="M20 11a8 8 0 0 0-14-4L4 9"/><path d="M4 4v5h5"/><path d="M4 13a8 8 0 0 0 14 4l2-2"/><path d="M20 20v-5h-5"/>',
    'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    'tag' => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.2"/>',
    'code' => '<path d="m8 8-5 4 5 4M16 8l5 4-5 4M14 5l-4 14"/>',
    'camera' => '<path d="M4 8h3l2-3h6l2 3h3v12H4z"/><circle cx="12" cy="13" r="3.5"/>',
    'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
    'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
    'rocket' => '<path d="M5 15c-1.5 1-2 4-2 6 2 0 5-.5 6-2"/><path d="m9 15-3-3c1-4 5-8 12-9 0 7-4.5 11-9 12z"/><circle cx="15" cy="9" r="1.5"/>',
    'chart' => '<path d="M4 20V4M4 20h16"/><path d="m7 15 4-4 3 3 5-6"/>',
    'gift' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v9h14v-9M12 8v13"/><path d="M12 8C10 4 6 4.5 7.5 7c.5.8 2.5 1 4.5 1zM12 8c2-4 6-3.5 4.5-1-.5.8-2.5 1-4.5 1z"/>',
    'credit-card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
    'minus' => '<path d="M5 12h14"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'trash' => '<path d="M4 7h16M10 11v6M14 11v6"/><path d="m6 7 1 13h10l1-13M9 7V4h6v3"/>',
    'book' => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 19V5M8 7h7"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'link' => '<path d="M10 14a4 4 0 0 0 6 0l3-3a4 4 0 0 0-6-6l-1 1"/><path d="M14 10a4 4 0 0 0-6 0l-3 3a4 4 0 0 0 6 6l1-1"/>',
    'award' => '<circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 7 5-3 5 3-1.5-7"/>',
    'leaf' => '<path d="M20 4C9 4 4 9 4 15c0 3 2 5 5 5 6 0 11-5 11-16z"/><path d="M4 21c2-6 6-9 11-11"/>',
    'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
    'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V4h6v3M3 13h18"/>',
    'graduation' => '<path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c3 2.5 9 2.5 12 0v-5"/>',
];

echo $paths[$name] ?? '';
