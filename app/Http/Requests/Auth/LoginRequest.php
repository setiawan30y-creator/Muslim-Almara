<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

// @2026-Alifiandra
class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:191'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['password' => 'kata sandi'];
    }
}
