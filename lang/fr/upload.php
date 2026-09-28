<?php

// Raisons d'un upload invalide (Niang\Core\Http\UploadedFile::errorMessage()), reprises telles
// quelles par le Validator.
return [
    'too_large' => 'Le fichier dépasse la taille maximale autorisée par le serveur (:max).',
    'partial' => "Le fichier n'a été reçu que partiellement.",
    'no_file' => "Aucun fichier n'a été envoyé.",
    'no_tmp_dir' => 'Dossier temporaire manquant sur le serveur.',
    'cant_write' => "Le serveur n'a pas pu écrire le fichier sur le disque.",
    'extension' => "Une extension PHP a interrompu l'envoi du fichier.",
    'moved' => 'Le fichier a déjà été déplacé.',
    'not_uploaded' => "Le fichier n'a pas été reçu par un envoi HTTP.",
    'unknown' => "Erreur inconnue lors de l'envoi du fichier.",
];
