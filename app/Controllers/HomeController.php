<?php

namespace App\Controllers;

use Niang\Core\Controller;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        return $this->view('home', [
            'title' => 'Bienvenue sur NiangPro',
            'framework' => 'NiangPro',
        ]);
    }

    public function hello(string $name): Response
    {
        return $this->json([
            'message' => "Bonjour, $name !",
        ]);
    }

    public function echoBody(Request $request): Response
    {
        return $this->json([
            'received' => $request->all(),
        ]);
    }
}
