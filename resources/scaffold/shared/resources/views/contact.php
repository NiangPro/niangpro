<?php
layout('layouts.app', [
    'title' => 'Contact',
    'active' => '/contact',
    'description' => 'Une question, un projet ? Écrivez-nous, nous répondons sous un jour ouvré.',
]);

// Coordonnées affichées à côté du formulaire : à renseigner dans config/site.php ('contact').
$contact = (array) config('site.contact', []);
$details = [
    'map-pin' => ['Adresse', $contact['address'] ?? null],
    'mail' => ['Email', $contact['email'] ?? null],
    'phone' => ['Téléphone', $contact['phone'] ?? null],
    'clock' => ['Horaires', $contact['hours'] ?? null],
];
?>
<?= component('components/page-hero', [
    'title' => 'Contact',
    'lead' => 'Une question, un projet ? Écrivez-nous, nous répondons sous un jour ouvré.',
    'crumbs' => [],
]) ?>

<section class="section">
    <div class="container" style="display:grid; gap:var(--space-7); grid-template-columns:repeat(auto-fit, minmax(min(100%, 20rem), 1fr)); align-items:start">
        <form class="card" method="POST" action="<?= e(route('contact')) ?>" novalidate>
            <?= csrf_field() ?>
            <?= component('components/field', ['name' => 'name', 'label' => 'Nom', 'autocomplete' => 'name']) ?>
            <?= component('components/field', ['name' => 'email', 'label' => 'Adresse email', 'type' => 'email', 'autocomplete' => 'email']) ?>
            <?= component('components/field', ['name' => 'message', 'label' => 'Votre message', 'rows' => 6]) ?>
            <button class="btn btn--primary btn--block" type="submit">Envoyer le message</button>
        </form>

        <aside class="stack" aria-label="Coordonnées">
            <?php foreach ($details as $icon => [$label, $value]): ?>
                <?php if ($value): ?>
                    <div class="cluster" style="align-items:flex-start; flex-wrap:nowrap">
                        <span class="card__icon" style="margin:0"><?= component('components/icon', ['name' => $icon]) ?></span>
                        <div><strong><?= e($label) ?></strong><br><span class="muted"><?= e($value) ?></span></div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </aside>
    </div>
</section>
