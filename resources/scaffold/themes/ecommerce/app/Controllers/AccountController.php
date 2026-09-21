<?php

namespace App\Controllers;

use App\Models\Order;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Http\Response;

/** Espace client. La connexion et l'inscription restent dans AuthController. */
class AccountController extends Controller
{
    /** « Mes commandes » — la route est protégée par le middleware Authenticate. */
    public function orders(): Response
    {
        return $this->view('shop/orders', [
            'orders' => Order::forUser((int) Auth::id()),
        ]);
    }
}
