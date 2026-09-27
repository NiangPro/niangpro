<?php

namespace App\Resources;

use Niang\Core\Http\JsonResource;

/** Jamais la ligne entière : elle contient le hachage du mot de passe et les secrets de connexion. */
class UserResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => (int) $this->resource['id'],
            'name' => $this->resource['name'],
            'email' => $this->resource['email'],
            'email_verified_at' => $this->resource['email_verified_at'] ?? null,
        ];
    }
}
