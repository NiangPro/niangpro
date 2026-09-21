<?php

namespace App\Controllers;

use App\Models\Product;
use App\Support\Cart;
use Niang\Core\Controller;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/** Panier en session (voir App\Support\Cart) : afficher, ajouter, changer une quantité, retirer. */
class CartController extends Controller
{
    public function show(): Response
    {
        $subtotal = Cart::subtotal();

        return $this->view('shop/cart', [
            'lines' => Cart::lines(),
            'subtotal' => $subtotal,
            'shipping' => Cart::shipping($subtotal),
        ]);
    }

    public function add(Request $request): Response
    {
        $data = $this->validate($request, [
            'product_id' => 'required|integer|min:1',
            'quantity' => 'nullable|integer|min:1|max:20',
        ]);

        if (!Cart::add((int) $data['product_id'], (int) ($data['quantity'] ?? 1))) {
            return $this->redirect('/boutique')->with('error', "Ce produit n'est plus disponible.");
        }

        $product = Product::find((int) $data['product_id']);

        return $this->redirect('/panier')->with('success', '« ' . ($product['name'] ?? 'Article') . ' » a été ajouté à votre panier.');
    }

    public function update(Request $request, string $id): Response
    {
        $data = $this->validate($request, ['quantity' => 'required|integer|min:0|max:20']);

        Cart::set((int) $id, (int) $data['quantity']);

        return $this->redirect('/panier');
    }

    public function remove(string $id): Response
    {
        Cart::remove((int) $id);

        return $this->redirect('/panier')->with('success', 'Article retiré du panier.');
    }
}
