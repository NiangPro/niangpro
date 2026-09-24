<?php

// Messages des règles de Niang\Core\Validation\Validator. « :attribute » est le nom du champ (ou
// son libellé : 3e argument de Validator::make(), attributes() d'une FormRequest, ou la section
// 'attributes' ci-dessous). Modifiez ce fichier librement : il appartient à votre projet.
return [
    'required' => 'Le champ :attribute est requis.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'numeric' => 'Le champ :attribute doit être numérique.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'array' => 'Le champ :attribute doit être un tableau.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'url' => 'Le champ :attribute doit être une URL valide.',
    'date' => 'Le champ :attribute doit être une date valide.',
    'date_format' => 'Le champ :attribute doit respecter le format :format.',
    'min' => 'Le champ :attribute doit contenir au moins :min caractères (ou valoir au moins :min).',
    'max' => 'Le champ :attribute ne doit pas dépasser :max caractères (ou :max).',
    'between' => 'Le champ :attribute doit être compris entre :min et :max.',
    'min_file' => 'Le fichier :attribute doit peser au moins :min Ko.',
    'max_file' => 'Le fichier :attribute ne doit pas dépasser :max Ko.',
    'between_file' => 'Le fichier :attribute doit peser entre :min et :max Ko.',
    'in' => "Le champ :attribute doit être l'une des valeurs suivantes : :values.",
    'not_in' => "Le champ :attribute ne doit pas être l'une des valeurs suivantes : :values.",
    'same' => 'Le champ :attribute doit être identique à :other.',
    'different' => 'Le champ :attribute doit être différent de :other.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'unique' => 'Cette valeur du champ :attribute est déjà utilisée.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'image' => 'Le fichier :attribute doit être une image (JPEG, PNG, GIF, WebP ou AVIF).',
    'mimes' => 'Le fichier :attribute doit être de type : :values.',
    'mimetypes' => 'Le fichier :attribute doit être de type : :values.',
    'dimensions' => "Les dimensions de l'image :attribute ne sont pas valides (:constraints).",
    'invalid' => 'Le champ :attribute est invalide.',
    'failed' => 'La validation a échoué.',

    // Libellés lisibles des champs, utilisés dans tous les messages, ex. 'email' => 'adresse email'.
    'attributes' => [],
];
