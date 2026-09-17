<?php

namespace App\Policies;

class PostPolicy
{
    // Ici : simple "il faut être connecté". Dans une vraie appli, comparez $user['id'] à un auteur.
    public function delete(?array $user, array $post): bool
    {
        return $user !== null;
    }

    public function update(?array $user, array $post): bool
    {
        return $user !== null;
    }
}
