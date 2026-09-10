<?php

namespace App\Controllers;

use App\Requests\ContactRequest;
use Niang\Core\Controller;
use Niang\Core\Http\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        return $this->view('contact');
    }

    /** ContactRequest est validée automatiquement par le Container avant l'appel de cette méthode. */
    public function store(ContactRequest $request): Response
    {
        // $request->validated() contient les données déjà validées, prêtes à être enregistrées.

        return $this->redirect('/contact')->with('success', 'Message envoyé, merci !');
    }
}
