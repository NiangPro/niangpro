<?php

namespace App\Requests;

use Niang\Core\Validation\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'device_name' => 'required|string|max:120',
        ];
    }
}
