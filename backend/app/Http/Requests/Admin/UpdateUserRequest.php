<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', Rule::in(['player', 'admin'])],
            'status' => ['sometimes', Rule::in(['active', 'blocked'])],
            'nickname' => ['sometimes', 'string', 'min:3', 'max:32', Rule::unique('users', 'nickname')->ignore($this->route('user'))],
        ];
    }
}
