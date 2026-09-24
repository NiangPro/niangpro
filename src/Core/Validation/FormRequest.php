<?php

namespace Niang\Core\Validation;

use Niang\Core\AuthorizationException;
use Niang\Core\Http\Request;

/**
 * À type-hinter directement dans une méthode de contrôleur : le Container la construit,
 * vérifie authorize() puis valide rules() automatiquement avant d'appeler le contrôleur.
 */
abstract class FormRequest extends Request
{
    private array $validated = [];

    abstract public function rules(): array;

    public function authorize(): bool
    {
        return true;
    }

    /** Surcharge de message, par 'champ.règle' (prioritaire) ou juste 'règle' — ex: ['email.required' => '...']. */
    public function messages(): array
    {
        return [];
    }

    /** Nom lisible d'un champ dans les messages par défaut, ex: ['email' => 'Adresse email']. */
    public function attributes(): array
    {
        return [];
    }

    /** @internal appelé par le Container lors de l'injection dans un contrôleur */
    public function validateResolved(): void
    {
        if (!$this->authorize()) {
            throw new AuthorizationException();
        }

        $this->validated = Validator::make($this->allWithFiles(), $this->rules(), $this->messages(), $this->attributes())->validate();
    }

    public function validated(): array
    {
        return $this->validated;
    }
}
