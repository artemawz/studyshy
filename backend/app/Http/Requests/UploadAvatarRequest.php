<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'avatar.required' => 'Bitte wähle ein Bild aus.',
            'avatar.image' => 'Die Datei muss ein Bild sein.',
            'avatar.mimes' => 'Erlaubt sind JPEG, PNG, WebP und GIF.',
            'avatar.max' => 'Das Bild darf maximal 2 MB groß sein.',
        ];
    }
}
