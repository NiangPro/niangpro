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

    /** @internal appelé par le Container lors de l'injection dans un contrôleur */
    public function validateResolved(): void
    {
        if (!$this->authorize()) {
            throw new AuthorizationException('Action non autorisée.');
        }

        $this->validated = Validator::make($this->all(), $this->rules())->validate();
    }

    public function validated(): array
    {
        return $this->validated;
    }
}
