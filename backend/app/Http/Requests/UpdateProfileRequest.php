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
            'degree' => ['sometimes', 'nullable', 'string', Rule::in(config('studyshy.degrees'))],
            'semester' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'interests' => ['sometimes', 'array', 'min:1', 'max:4'],
            'interests.*' => ['required_with:interests', 'string', 'min:2', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'uni.in' => 'Bitte wähle eine unterstützte Hochschule.',
            'degree.in' => 'Bitte wähle einen gültigen Abschluss.',
            'interests.max' => 'Du kannst maximal 4 Interessen auswählen.',
            'interests.*.max' => 'Ein Interesse darf höchstens 30 Zeichen haben.',
        ];
    }
}
