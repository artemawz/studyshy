<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'pub_name' => ['sometimes', 'string', 'min:2', 'max:64'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'uni' => ['sometimes', 'string', Rule::in(config('studyshy.universities'))],
            'course' => ['sometimes', 'string', 'max:255'],
            'semester' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'interests' => ['sometimes', 'array', 'min:1'],
            'interests.*' => ['required_with:interests', 'string', 'min:2', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'uni.in' => 'Bitte wähle eine unterstützte Hochschule.',
        ];
    }
}
