<?php

namespace App\Controllers;

use Niang\Core\Controller;
use Niang\Core\Http\Response;

/** La landing est une seule page à sections ancrées, son contenu vit dans config/site.php. */
class LandingController extends Controller
{
    public function home(): Response
    {
        return $this->view('pages/home');
    }

    public function legal(): Response
    {
        return $this->view('pages/legal');
    }
}
