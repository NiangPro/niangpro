<?php

namespace App\Support;

/** Sections de la barre latérale de l'administration du blog (lue par resources/views/admin/layouts/app.php). */
class AdminMenu
{
    /** @return list<array{label: string, items: list<array{label: string, href: string, icon: string}>}> */
    public static function sections(): array
    {
        return [
            ['label' => 'Pilotage', 'items' => [
                ['label' => 'Tableau de bord', 'href' => '/admin', 'icon' => 'grid'],
            ]],
            ['label' => 'Contenu', 'items' => [
                ['label' => 'Articles', 'href' => '/admin/articles', 'icon' => 'pen'],
                ['label' => 'Catégories', 'href' => '/admin/categories', 'icon' => 'folder'],
                ['label' => 'Tags', 'href' => '/admin/tags', 'icon' => 'hash'],
            ]],
            ['label' => 'Équipe', 'items' => [
                ['label' => 'Utilisateurs', 'href' => '/admin/utilisateurs', 'icon' => 'users'],
            ]],
            ['label' => 'Configuration', 'items' => [
                ['label' => 'Paramètres', 'href' => '/admin/parametres', 'icon' => 'settings'],
            ]],
        ];
    }
}
