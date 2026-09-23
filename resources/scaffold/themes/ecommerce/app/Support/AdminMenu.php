<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;

/**
 * Sections de la barre latérale de l'administration de la boutique (lue par
 * resources/views/admin/layouts/app.php). Les pastilles signalent ce qui attend une action.
 */
class AdminMenu
{
    /** @return list<array{label: string, items: list<array{label: string, href: string, icon: string, badge?: int}>}> */
    public static function sections(): array
    {
        return [
            ['label' => 'Pilotage', 'items' => [
                ['label' => 'Tableau de bord', 'href' => '/admin', 'icon' => 'grid'],
            ]],
            ['label' => 'Ventes', 'items' => [
                ['label' => 'Commandes', 'href' => '/admin/commandes', 'icon' => 'package', 'badge' => Order::query()->where('status', 'pending')->count()],
                ['label' => 'Clients', 'href' => '/admin/clients', 'icon' => 'users'],
            ]],
            ['label' => 'Catalogue', 'items' => [
                ['label' => 'Produits', 'href' => '/admin/produits', 'icon' => 'tag'],
                ['label' => 'Catégories', 'href' => '/admin/categories', 'icon' => 'folder'],
                ['label' => 'Stock', 'href' => '/admin/stock', 'icon' => 'layers', 'badge' => Product::query()->where('stock', '<=', Product::LOW_STOCK)->count()],
            ]],
            ['label' => 'Configuration', 'items' => [
                ['label' => 'Paramètres', 'href' => '/admin/parametres', 'icon' => 'settings'],
            ]],
        ];
    }
}
