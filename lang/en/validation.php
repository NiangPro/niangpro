<?php

// Messages for the rules of Niang\Core\Validation\Validator. ":attribute" is the field name (or its
// label: 3rd argument of Validator::make(), attributes() of a FormRequest, or the 'attributes'
// section below). Edit this file freely: it belongs to your project.
return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute field must be a string.',
    'numeric' => 'The :attribute field must be a number.',
    'integer' => 'The :attribute field must be an integer.',
    'boolean' => 'The :attribute field must be true or false.',
    'array' => 'The :attribute field must be an array.',
    'email' => 'The :attribute field must be a valid email address.',
    'url' => 'The :attribute field must be a valid URL.',
    'date' => 'The :attribute field must be a valid date.',
    'date_format' => 'The :attribute field must match the format :format.',
    'min' => 'The :attribute field must be at least :min characters (or at least :min).',
    'max' => 'The :attribute field must not exceed :max characters (or :max).',
    'between' => 'The :attribute field must be between :min and :max.',
    'min_file' => 'The :attribute file must be at least :min KB.',
    'max_file' => 'The :attribute file must not exceed :max KB.',
    'between_file' => 'The :attribute file must be between :min and :max KB.',
    'in' => 'The :attribute field must be one of the following values: :values.',
    'not_in' => 'The :attribute field must not be one of the following values: :values.',
    'same' => 'The :attribute field must match :other.',
    'different' => 'The :attribute field must be different from :other.',
    'regex' => 'The :attribute field format is invalid.',
    'confirmed' => 'The :attribute field confirmation does not match.',
    'unique' => 'This :attribute is already taken.',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute field must be a file.',
    'image' => 'The :attribute file must be an image (JPEG, PNG, GIF, WebP or AVIF).',
    'mimes' => 'The :attribute file must be of type: :values.',
    'mimetypes' => 'The :attribute file must be of type: :values.',
    'dimensions' => 'The :attribute image has invalid dimensions (:constraints).',
    'invalid' => 'The :attribute field is invalid.',
    'failed' => 'The given data was invalid.',

    // Readable field labels, used in every message, e.g. 'email' => 'email address'.
    'attributes' => [],
];
