<?php

namespace App\Controllers;

use Niang\Core\Controller;
use Niang\Core\Http\Response;

/**
 * Pages du site vitrine. Le contenu (services, réalisations, FAQ...) vit dans config/site.php :
 * ces méthodes se contentent de rendre les vues, qui le lisent elles-mêmes via config().
 */
class VitrineController extends Controller
{
    public function home(): Response
    {
        return $this->view('pages/home');
    }

    public function about(): Response
    {
        return $this->view('pages/about');
    }

    public function services(): Response
    {
        return $this->view('pages/services');
    }

    public function works(): Response
    {
        return $this->view('pages/works');
    }

    public function faq(): Response
    {
        return $this->view('pages/faq');
    }

    public function legal(): Response
    {
        return $this->view('pages/legal');
    }

    public function privacy(): Response
    {
        return $this->view('pages/privacy');
    }
}
