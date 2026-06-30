<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'text' => ['nullable', 'string', 'max:2000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'text.required_without' => 'Bitte schreibe eine Nachricht oder hänge eine Datei an.',
            'attachment.mimes' => 'Erlaubt sind Bilder (JPG, PNG, WebP, GIF) und PDF-Dateien.',
            'attachment.max' => 'Die Datei darf maximal 5 MB groß sein.',
        ];
    }
}
