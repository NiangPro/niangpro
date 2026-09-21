<?php layout('layouts.app', ['title' => (string) $status]); ?>

<section class="error-page">
    <div class="container">
        <p class="error-page__code"><?= e((string) $status) ?></p>
        <h1><?= e($message) ?></h1>
        <p><a class="btn btn--primary" href="/">Retour à l'accueil</a></p>
    </div>
</section>
