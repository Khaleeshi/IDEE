<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectDocumentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png', 'max:'.config('idee.documents.max_kb')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Donnez un nom au document, par exemple « Business plan 2026 ».',
            'file.required' => 'Choisissez le fichier à déposer.',
            'file.extensions' => 'Déposez un PDF, un document Office ou une photo.',
            'file.max' => 'Le fichier est trop lourd.',
        ];
    }
}
