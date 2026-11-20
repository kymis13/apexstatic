<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:rank,steam,stats,access,appeal'],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
