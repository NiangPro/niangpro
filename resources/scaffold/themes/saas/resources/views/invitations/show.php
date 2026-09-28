<?php layout('layouts.app', ['title' => 'Invitation']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:32rem">
        <div class="card text-center">
            <h1 style="font-size: var(--step-2)">Rejoindre « <?= e($organization['name']) ?> »</h1>
            <?php if ($matches): ?>
                <form method="POST" action="/invitations/<?= e($token) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--primary btn--lg" type="submit">Accepter l'invitation</button>
                </form>
            <?php else: ?>
                <p class="muted">Cette invitation est destinée à <strong><?= e($invitation['email']) ?></strong>. Connectez-vous avec ce compte pour l'accepter.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
