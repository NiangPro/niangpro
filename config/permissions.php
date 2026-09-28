<?php

/*
 * Rôles et permissions (Niang\Core\Permission). Le rôle d'un utilisateur est lu dans sa colonne
 * `role` (ajoutée par le module d'administration des thèmes boutique et blog ; sinon, par une
 * migration : $table->string('role')->default('user')). « * » accorde tout ; « posts.* » toutes
 * les permissions qui commencent par « posts. ».
 */
return [
    'column' => 'role',

    'roles' => [
        'admin' => ['*'],
        'editor' => ['posts.*', 'comments.moderate'],
        'user' => [],
    ],
];
