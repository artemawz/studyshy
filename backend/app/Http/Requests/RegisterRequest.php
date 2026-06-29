<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'uni' => ['required', 'string', Rule::in(config('studyshy.universities'))],
            'course' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'integer', 'min:1', 'max:20'],
            'interests' => ['required', 'array', 'min:1'],
            'interests.*' => ['required', 'string', 'min:2', 'max:50'],
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
