<?php

use App\Models\Membership;
use App\Support\Team;
use Niang\Core\Auth;

layout('layouts.app', ['title' => 'Membres']);
?>
<?= component('components/team-nav', ['active' => '/membres']) ?>

<section class="section">
    <div class="container container--narrow" style="max-width:48rem">
        <h1 style="font-size: var(--step-3)">Membres</h1>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Nom</th><th>Rôle</th><?php if ($isAdmin): ?><th></th><?php endif; ?></tr></thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?= e($member['name']) ?><br><span class="muted"><?= e($member['email']) ?></span></td>
                            <td>
                                <?php if ($isAdmin && ($isOwner || $member['role'] !== 'owner')): ?>
                                    <form method="POST" action="<?= e(Team::url('/membres/' . $member['id'] . '/role')) ?>" class="cluster">
                                        <?= csrf_field() ?>
                                        <select name="role" aria-label="Rôle de <?= e($member['name']) ?>">
                                            <?php foreach (Membership::ROLES as $value => $label): ?>
                                                <?php if ($value === 'owner' && !$isOwner) { continue; } ?>
                                                <option value="<?= e($value) ?>"<?= $member['role'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn--ghost btn--sm" type="submit">Changer</button>
                                    </form>
                                <?php else: ?>
                                    <?= e(Membership::ROLES[$member['role']] ?? $member['role']) ?>
                                <?php endif; ?>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td>
                                    <?php if ((int) $member['user_id'] !== (int) Auth::id() && ($isOwner || $member['role'] !== 'owner')): ?>
                                        <form method="POST" action="<?= e(Team::url('/membres/' . $member['id'] . '/retirer')) ?>">
                                            <?= csrf_field() ?>
                                            <button class="btn btn--ghost btn--sm" type="submit">Retirer</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($isAdmin): ?>
            <?php if ($invitations !== []): ?>
                <h2 style="font-size: var(--step-1); margin-top: var(--space-6)">Invitations en attente</h2>
                <ul class="stack">
                    <?php foreach ($invitations as $invitation): ?>
                        <li class="cluster">
                            <span><?= e($invitation['email']) ?> (<?= e(Membership::ROLES[$invitation['role']] ?? '') ?>)</span>
                            <form method="POST" action="<?= e(Team::url('/membres/invitations/' . $invitation['id'] . '/annuler')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--ghost btn--sm" type="submit">Annuler</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($canInvite): ?>
                <form class="card" method="POST" action="<?= e(Team::url('/membres/invitations')) ?>" novalidate style="margin-top: var(--space-6)">
                    <h2 style="font-size: var(--step-1)">Inviter</h2>
                    <?= csrf_field() ?>
                    <?= field('email', 'Adresse email', ['type' => 'email']) ?>
                    <div class="field">
                        <label for="role">Rôle</label>
                        <select id="role" name="role"><option value="member">Membre</option><option value="admin">Administrateur</option></select>
                    </div>
                    <button class="btn btn--primary" type="submit">Envoyer l'invitation</button>
                </form>
            <?php else: ?>
                <p class="alert alert--info" style="margin-top: var(--space-6)">Limite de membres de votre plan atteinte. <a href="<?= e(Team::url('/abonnement')) ?>">Passer au plan supérieur</a></p>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="<?= e(Team::url('/membres/quitter')) ?>" style="margin-top: var(--space-6)">
            <?= csrf_field() ?>
            <button class="btn btn--ghost btn--sm" type="submit">Quitter l'organisation</button>
        </form>
    </div>
</section>
