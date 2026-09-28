<?php

namespace App\Controllers;

use App\Billing\Billing;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Http\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        return $this->view('pages/home', ['user' => Auth::user(), 'plans' => Billing::plans()]);
    }
}
