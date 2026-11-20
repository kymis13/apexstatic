<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'alpha_dash', 'max:40', 'unique:legends,slug'],
            'name' => ['required', 'string', 'max:60'],
            'real_name' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::in(['assault', 'skirmisher', 'recon', 'support', 'controller'])],
            'difficulty' => ['integer', 'between:1,3'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'lore' => ['nullable', 'string', 'max:2000'],
            'passive_name' => ['nullable', 'string', 'max:100'],
            'passive_desc' => ['nullable', 'string', 'max:500'],
            'tactical_name' => ['nullable', 'string', 'max:100'],
            'tactical_desc' => ['nullable', 'string', 'max:500'],
            'ultimate_name' => ['nullable', 'string', 'max:100'],
            'ultimate_desc' => ['nullable', 'string', 'max:500'],
            'forte' => ['nullable', 'string', 'max:180'],
            'tip' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'string', 'max:255'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'pick_rate' => ['numeric', 'between:0,100'],
            'release_season' => ['nullable', 'string', 'max:40'],
            'is_active' => ['boolean'],
        ];
    }
}
