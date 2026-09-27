<?php

namespace App\Requests;

use Niang\Core\Validation\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:120',
            // Code de double authentification, si elle est activée sur le compte.
            'code' => 'nullable|string',
        ];
    }
}
