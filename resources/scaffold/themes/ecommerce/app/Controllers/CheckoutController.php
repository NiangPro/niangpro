<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Requests\CheckoutRequest;
use App\Support\Cart;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Response;
use Niang\Core\Session;

/**
 * Commande : coordonnées de livraison, création de la commande, confirmation.
 *
 * TODO(paiement) : AUCUN PAIEMENT RÉEL n'est branché. store() enregistre la commande avec le statut
 * « pending » (en attente de paiement) et affiche directement la confirmation. Pour encaisser :
 *   1. après avoir créé la commande, ouvrir une session de paiement chez votre prestataire (Stripe
 *      Checkout, PayPal, Wave, PayDunya... — leur SDK ou leur API HTTP) avec le total et la référence ;
 *   2. rediriger le client vers la page de paiement du prestataire, en donnant confirmation() comme
 *      URL de retour ;
 *   3. ne passer la commande à « paid » (Order::update) que depuis le webhook du prestataire, jamais
 *      depuis la page de retour, que le client peut ouvrir à la main ;
 *   4. n'ajouter la dépendance du prestataire qu'à votre propre projet (composer require) : le
 *      framework n'en impose aucune.
 * Tant que ce n'est pas fait, config('site.demo_notice') affiche un avertissement sur la confirmation.
 */
class CheckoutController extends Controller
{
    public function show(): Response
    {
        if (Cart::count() === 0) {
            return $this->redirect('/panier');
        }

        $subtotal = Cart::subtotal();
        $shipping = Cart::shipping($subtotal);

        return $this->view('shop/checkout', [
            'lines' => Cart::lines(),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'customer' => Auth::user() ?? [],
        ]);
    }

    /** CheckoutRequest est validée automatiquement par le Container avant l'appel de cette méthode. */
    public function store(CheckoutRequest $request): Response
    {
        $lines = Cart::lines();

        if (!$lines) {
            return $this->redirect('/panier');
        }

        $reference = null;

        $created = DB::transaction(function () use ($request, $lines, &$reference): bool {
            // Relit le stock au moment d'acheter : il a pu baisser depuis l'ajout au panier. Une
            // seule requête (whereIn) pour tous les articles du panier plutôt qu'un Product::find()
            // par ligne (N+1 — audit de performance, mesuré avant/après sur le Router et le
            // Container : ici pas besoin de mesurer, le nombre de requêtes divisé par la taille du
            // panier parle de lui-même).
            $productIds = array_column(array_column($lines, 'product'), 'id');
            $freshProducts = array_column(Product::query()->whereIn('id', $productIds)->get(), null, 'id');

            foreach ($lines as $line) {
                $fresh = $freshProducts[$line['product']['id']] ?? null;

                if (!$fresh || (int) $fresh['stock'] < $line['quantity']) {
                    return false;
                }
            }

            $subtotal = array_sum(array_column($lines, 'line_total'));
            $shipping = Cart::shipping($subtotal);
            $reference = 'CMD-' . strtoupper(bin2hex(random_bytes(4)));

            // forceCreate : validated() ne contient que les coordonnées validées par CheckoutRequest ;
            // montants, statut et user_id viennent du serveur, jamais du formulaire.
            $orderId = Order::forceCreate([
                ...$request->validated(),
                'user_id' => Auth::id(),
                'reference' => $reference,
                'subtotal_cents' => $subtotal,
                'shipping_cents' => $shipping,
                'total_cents' => $subtotal + $shipping,
                'status' => 'pending',
            ]);

            foreach ($lines as $line) {
                OrderItem::forceCreate([
                    'order_id' => $orderId,
                    'product_id' => $line['product']['id'],
                    'name' => $line['product']['name'],
                    'unit_price_cents' => $line['product']['price_cents'],
                    'quantity' => $line['quantity'],
                ]);

                Product::update($line['product']['id'], ['stock' => (int) $line['product']['stock'] - $line['quantity']]);
            }

            return true;
        });

        if (!$created) {
            return $this->redirect('/panier')->with('error', "Un article n'est plus disponible en quantité suffisante : vérifiez votre panier.");
        }

        Cart::clear();
        Session::put('last_order', $reference);

        return $this->redirect("/commande/confirmation/$reference");
    }

    /** Visible par le client qui vient de commander (session) ou par le titulaire du compte, jamais par un simple visiteur. */
    public function confirmation(string $reference): Response
    {
        $order = Order::findByReference($reference);

        $allowed = $order && (
            Session::get('last_order') === $reference
            || (Auth::check() && (string) $order['user_id'] === (string) Auth::id())
        );

        if (!$order || !$allowed) {
            abort(404);
        }

        return $this->view('shop/confirmation', ['order' => $order, 'items' => Order::items($order['id'])]);
    }
}
