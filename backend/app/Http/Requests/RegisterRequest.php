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
            'email' => [
                'required',
                'email',
                'regex:/^[^\s@]+@[^\s@]+\.(de|com)$/i',
                'max:255',
                'unique:users,email',
            ],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[^A-Za-z0-9]/'],
            'pub_name' => ['nullable', 'string', 'min:2', 'max:64'],
            'uni' => ['required', 'string', Rule::in(config('studyshy.universities'))],
            'course' => ['required', 'string', 'max:255'],
            'degree' => ['nullable', 'string', Rule::in(config('studyshy.degrees'))],
            'semester' => ['required', 'integer', 'min:1', 'max:20'],
            'interests' => ['required', 'array', 'min:1', 'max:4'],
            'interests.*' => ['required', 'string', 'min:2', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen, einen Großbuchstaben und ein Sonderzeichen enthalten.',
            'password.regex' => 'Das Passwort muss mindestens 8 Zeichen, einen Großbuchstaben und ein Sonderzeichen enthalten.',
            'uni.in' => 'Bitte wähle eine unterstützte Hochschule.',
            'degree.in' => 'Bitte wähle einen gültigen Abschluss.',
            'interests.max' => 'Du kannst maximal 4 Interessen auswählen.',
            'interests.*.max' => 'Ein Interesse darf höchstens 30 Zeichen haben.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse ein (mit @ und .de oder .com).',
            'email.regex' => 'Bitte gib eine gültige E-Mail-Adresse ein (mit @ und .de oder .com).',
            'email.unique' => 'E-Mail-Adresse wird schon benutzt.',
        ];
    }
}
