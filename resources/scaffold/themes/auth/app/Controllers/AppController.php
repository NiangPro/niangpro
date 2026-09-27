<?php

namespace App\Controllers;

use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Http\Response;

class AppController extends Controller
{
    public function home(): Response
    {
        return $this->view('pages/home', ['user' => Auth::user()]);
    }

    /** L'espace membre : le point de départ de votre produit. */
    public function dashboard(): Response
    {
        return $this->view('pages/dashboard', ['user' => Auth::user()]);
    }
}
