<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[\p{L}\p{N}_-]+$/u', 'unique:users,nickname'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'device_name' => ['sometimes', 'string', 'max:64'],
            'accept_policy' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.regex' => 'Никнейм может содержать только буквы, цифры, дефис и подчёркивание.',
            'nickname.unique' => 'Этот никнейм уже занят.',
            'email.unique' => 'Этот email уже зарегистрирован.',
            'password.confirmed' => 'Пароли не совпадают.',
            'accept_policy.accepted' => 'Нужно согласие с политикой безопасности.',
        ];
    }
}
