<?php
/** @var list<array<string, mixed>> $orders  commandes du client connecté, avec leurs lignes (clé « items ») */

use App\Models\Order;
use App\Support\Money;
use Niang\Core\Auth;

layout('layouts.app', ['title' => 'Mes commandes', 'active' => '/compte']);
?>
<?= component('components/page-hero', ['title' => 'Mes commandes', 'lead' => 'Connecté en tant que ' . (Auth::user()['email'] ?? ''), 'crumbs' => []]) ?>

<section class="section">
    <div class="container">
        <?php if (!$orders): ?>
            <div class="card text-center" style="align-items:center; max-width:32rem; margin-inline:auto">
                <h2 style="font-size: var(--step-2)">Aucune commande pour l'instant</h2>
                <p class="muted">Vos prochains achats apparaîtront ici.</p>
                <a class="btn btn--primary" href="/boutique">Voir la boutique</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <caption class="sr-only">Historique de vos commandes</caption>
                    <thead>
                        <tr><th scope="col">Référence</th><th scope="col">Date</th><th scope="col">Articles</th><th scope="col">Total</th><th scope="col">Statut</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <th scope="row"><a href="/commande/confirmation/<?= e($order['reference']) ?>"><?= e($order['reference']) ?></a></th>
                                <td><?= e(date('d/m/Y', (int) strtotime((string) $order['created_at']))) ?></td>
                                <td><?= (int) array_sum(array_column($order['items'], 'quantity')) ?></td>
                                <td><?= e(Money::format((int) $order['total_cents'])) ?></td>
                                <td><span class="badge badge--soft"><?= e(Order::statusLabel((string) $order['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <form method="POST" action="/logout" style="margin-top: var(--space-6); text-align:center">
            <?= csrf_field() ?>
            <button class="btn btn--ghost" type="submit">Se déconnecter</button>
        </form>
    </div>
</section>
