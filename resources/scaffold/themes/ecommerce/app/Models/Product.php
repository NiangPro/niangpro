<?php

namespace App\Models;

use Niang\Core\Database\Model;

/**
 * Produit du catalogue. Les prix sont des entiers en centimes d'euro (1290 = 12,90 €) : jamais de
 * flottants pour de l'argent. Voir database/migrations/*_create_products_table.php.
 *
 * Colonnes : name, slug (unique, sert d'URL), category, description, price_cents, old_price_cents
 * (prix barré, null hors promotion), image (chemin sous public/, null = illustration générée),
 * stock, featured (mis en avant sur l'accueil).
 */
class Product extends Model
{
    /** En dessous de ce stock (inclus), le produit est signalé dans l'administration. */
    public const LOW_STOCK = 5;

    public static function findBySlug(string $slug): ?array
    {
        return static::query()->where('slug', $slug)->first();
    }

    /** Produit en promotion : son ancien prix est renseigné et supérieur au prix actuel. */
    public static function isOnSale(array $product): bool
    {
        return !empty($product['old_price_cents']) && (int) $product['old_price_cents'] > (int) $product['price_cents'];
    }

    /** Pourcentage de remise arrondi, ex. 20 pour un produit passé de 50 € à 40 €. */
    public static function discountPercent(array $product): int
    {
        if (!self::isOnSale($product)) {
            return 0;
        }

        return (int) round(100 - (int) $product['price_cents'] * 100 / (int) $product['old_price_cents']);
    }
}
