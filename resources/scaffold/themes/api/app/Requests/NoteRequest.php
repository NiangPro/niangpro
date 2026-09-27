<?php

namespace App\Requests;

use Niang\Core\Validation\FormRequest;

class NoteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'body' => 'nullable|string|max:10000',
            'done' => 'nullable|boolean',
        ];
    }
}
