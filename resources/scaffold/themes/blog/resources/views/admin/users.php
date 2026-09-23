<?php
/**
 * @var list<array<string, mixed>> $users
 * @var int $currentId
 */

use App\Models\User;
use App\Support\AdminFormat;

layout('admin.layouts.app', [
    'title' => 'Utilisateurs',
    'active' => '/admin/utilisateurs',
    'subtitle' => 'Les comptes de l\'équipe. Un administrateur accède à cet espace ; un compte « membre » non.',
]);
?>
<div class="admin-grid admin-grid--main">
    <section class="admin-card admin-card--flush">
        <header class="admin-card__head"><h2><?= count($users) ?> compte<?= count($users) > 1 ? 's' : '' ?></h2></header>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Créé</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php $isAdmin = User::isAdmin($user); ?>
                        <tr>
                            <td>
                                <div class="admin-cell">
                                    <span class="admin-avatar"><?= e(mb_strtoupper(mb_substr((string) $user['name'], 0, 1))) ?></span>
                                    <span><span class="admin-table__main"><?= e($user['name']) ?><?= (int) $user['id'] === $currentId ? ' <span class="admin-chip">vous</span>' : '' ?></span><span class="admin-table__sub"><?= e($user['email']) ?></span></span>
                                </div>
                            </td>
                            <td><span class="admin-badge admin-badge--<?= $isAdmin ? 'violet' : 'sky' ?>"><?= $isAdmin ? 'Administrateur' : 'Membre' ?></span></td>
                            <td class="admin-nowrap"><?= e(AdminFormat::date($user['created_at'])) ?></td>
                            <td>
                                <div class="admin-table__actions">
                                    <?php if ((int) $user['id'] !== $currentId): ?>
                                        <form method="POST" action="/admin/utilisateurs/<?= (int) $user['id'] ?>/role">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="role" value="<?= $isAdmin ? 'user' : 'admin' ?>">
                                            <button class="admin-btn admin-btn--ghost admin-btn--sm" type="submit"><?= $isAdmin ? 'Retirer l\'accès admin' : 'Rendre administrateur' ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card">
        <header class="admin-card__head"><h2>Nouveau compte</h2></header>
        <form class="admin-form" method="POST" action="/admin/utilisateurs" novalidate>
            <?= csrf_field() ?>
            <?= component('admin/components/field', ['name' => 'name', 'label' => 'Nom', 'required' => true]) ?>
            <?= component('admin/components/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true]) ?>
            <?= component('admin/components/field', ['name' => 'password', 'label' => 'Mot de passe provisoire', 'type' => 'password', 'required' => true, 'hint' => '8 caractères minimum, à transmettre à la personne.', 'attrs' => 'autocomplete="new-password"']) ?>
            <?= component('admin/components/field', ['name' => 'role', 'label' => 'Rôle', 'type' => 'select', 'options' => ['user' => 'Membre', 'admin' => 'Administrateur'], 'value' => 'admin']) ?>
            <div class="admin-form__actions"><button class="admin-btn admin-btn--primary" type="submit">Créer le compte</button></div>
        </form>
    </section>
</div>
