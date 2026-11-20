<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLegendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['sometimes', 'alpha_dash', 'max:40', Rule::unique('legends', 'slug')->ignore($this->route('legend'))],
            'name' => ['sometimes', 'string', 'max:60'],
            'real_name' => ['sometimes', 'string', 'max:100'],
            'role' => ['sometimes', Rule::in(['assault', 'skirmisher', 'recon', 'support', 'controller'])],
            'difficulty' => ['sometimes', 'integer', 'between:1,3'],
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
            'pick_rate' => ['sometimes', 'numeric', 'between:0,100'],
            'release_season' => ['nullable', 'string', 'max:40'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
