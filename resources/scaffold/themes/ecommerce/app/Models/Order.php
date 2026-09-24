<?php

namespace App\Models;

use Niang\Core\Database\Model;

/**
 * Commande. Créée avec le statut « pending » (en attente de paiement) : aucun paiement réel
 * n'est branché, voir le TODO de App\Controllers\CheckoutController.
 */
class Order extends Model
{
    /**
     * Seules les coordonnées saisies par le client : montants, statut, référence et user_id sont
     * calculés par le serveur (CheckoutController, via forceCreate).
     */
    protected static array $fillable = ['name', 'email', 'phone', 'address', 'postal_code', 'city'];

    public static function items(int|string $orderId): array
    {
        return static::hasMany($orderId, OrderItem::class, 'order_id');
    }

    public static function findByReference(string $reference): ?array
    {
        return static::query()->where('reference', $reference)->first();
    }

    /**
     * Commandes d'un client avec leurs lignes (clé « items »), la plus récente d'abord.
     *
     * @return list<array<string, mixed>>
     */
    public static function forUser(int|string $userId): array
    {
        return static::with('items')->where('user_id', $userId)->orderBy('id', 'desc')->get();
    }

    /** Utilisable avec Order::with('items') — une seule requête pour les lignes de toutes les commandes. */
    public static function eagerLoadable(): array
    {
        return [
            'items' => fn (array $orders) => static::loadMany($orders, 'items', OrderItem::class, 'order_id'),
        ];
    }

    /** Statuts possibles, dans l'ordre du parcours d'une commande. */
    public const STATUSES = ['pending', 'paid', 'shipped', 'cancelled'];

    /** Libellé lisible d'un statut. */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente de paiement',
            'paid' => 'Payée',
            'shipped' => 'Expédiée',
            'cancelled' => 'Annulée',
            default => ucfirst($status),
        };
    }

    /** Couleur du badge de statut dans l'administration. */
    public static function statusTone(string $status): string
    {
        return match ($status) {
            'pending' => 'amber',
            'paid' => 'teal',
            'shipped' => 'green',
            'cancelled' => 'rose',
            default => 'sky',
        };
    }
}
