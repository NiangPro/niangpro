<?php

namespace App\Support;

use App\Models\Product;
use Niang\Core\Session;

/**
 * Panier stocké en session : [id du produit => quantité]. Rien n'est écrit en base avant la commande.
 * Les produits sont relus à chaque affichage, donc un prix ou un stock modifié est toujours à jour.
 */
class Cart
{
    private const KEY = 'cart';

    /** Quantité maximale d'un même produit dans un panier, quel que soit le stock. */
    private const MAX_PER_LINE = 20;

    /** @return array<int, int> */
    public static function raw(): array
    {
        $cart = Session::get(self::KEY, []);

        return is_array($cart) ? $cart : [];
    }

    /** Ajoute au panier, dans la limite du stock. Faux si le produit n'existe pas ou n'est plus disponible. */
    public static function add(int $productId, int $quantity = 1): bool
    {
        $product = Product::find($productId);

        if (!$product || (int) $product['stock'] < 1) {
            return false;
        }

        $cart = self::raw();
        $cart[$productId] = self::clamp(($cart[$productId] ?? 0) + $quantity, $product);
        Session::put(self::KEY, $cart);

        return true;
    }

    /** Fixe la quantité d'une ligne ; 0 (ou moins) la retire. */
    public static function set(int $productId, int $quantity): void
    {
        $cart = self::raw();
        $product = Product::find($productId);

        if ($quantity < 1 || !$product || !isset($cart[$productId])) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = self::clamp($quantity, $product);
        }

        Session::put(self::KEY, $cart);
    }

    public static function remove(int $productId): void
    {
        self::set($productId, 0);
    }

    public static function clear(): void
    {
        Session::forget(self::KEY);
    }

    /** Nombre total d'articles (somme des quantités). */
    public static function count(): int
    {
        return array_sum(self::raw());
    }

    /**
     * Lignes du panier avec leur produit. Un produit supprimé du catalogue disparaît du panier ;
     * une quantité supérieure au stock actuel est ramenée au stock.
     *
     * @return list<array{product: array<string, mixed>, quantity: int, line_total: int}>
     */
    public static function lines(): array
    {
        $cart = self::raw();

        if (!$cart) {
            return [];
        }

        $lines = [];

        foreach (Product::query()->whereIn('id', array_keys($cart))->get() as $product) {
            $quantity = self::clamp((int) $cart[$product['id']], $product);

            if ($quantity < 1) {
                continue;
            }

            $lines[] = [
                'product' => $product,
                'quantity' => $quantity,
                'line_total' => $quantity * (int) $product['price_cents'],
            ];
        }

        return $lines;
    }

    /** Sous-total en centimes, hors livraison. */
    public static function subtotal(): int
    {
        return array_sum(array_column(self::lines(), 'line_total'));
    }

    /** Frais de livraison en centimes : offerts au-delà du seuil de config/site.php, nuls pour un panier vide. */
    public static function shipping(int $subtotal): int
    {
        if ($subtotal <= 0 || $subtotal >= (int) config('site.shipping.free_from_cents', 5000)) {
            return 0;
        }

        return (int) config('site.shipping.flat_cents', 490);
    }

    /** @param array<string, mixed> $product */
    private static function clamp(int $quantity, array $product): int
    {
        return max(0, min($quantity, (int) $product['stock'], self::MAX_PER_LINE));
    }
}
