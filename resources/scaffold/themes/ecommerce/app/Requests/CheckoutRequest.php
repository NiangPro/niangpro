<?php

namespace App\Requests;

use Niang\Core\Validation\FormRequest;

/** Coordonnées de livraison saisies à l'étape « Commande ». */
class CheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:150',
            // min/max comparent la valeur d'une chaîne numérique (« 44000 » > 10) : un code postal
            // ou un téléphone se valident donc par leur forme, pas par leur longueur.
            'phone' => ['nullable', 'regex:/^[0-9 +().-]{6,20}$/'],
            'address' => 'required|string|min:5|max:200',
            'postal_code' => ['required', 'regex:/^[0-9A-Za-z -]{4,10}$/'],
            'city' => 'required|string|min:2|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nom complet',
            'email' => 'Adresse email',
            'phone' => 'Téléphone',
            'address' => 'Adresse',
            'postal_code' => 'Code postal',
            'city' => 'Ville',
        ];
    }
}
