<?php

namespace App\Http\Requests;

use App\Services\Messaging\MessageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMessageRequest extends FormRequest
{
    /**
     * Formats acceptés : documents courants, photos et enregistrements vocaux des téléphones.
     */
    private const ALLOWED = 'pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,m4a,aac,ogg,oga,opus,wav,webm,amr,3gp';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'required_without:attachment', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'extensions:'.self::ALLOWED, 'max:'.config('idee.messages.max_document_kb')],
        ];
    }

    /**
     * Les messages vocaux ont leur propre limite de taille.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $file = $this->file('attachment');
            $limit = config('idee.messages.max_voice_kb');

            if ($file && app(MessageService::class)->isVoice($file) && $file->getSize() > $limit * 1024) {
                $validator->errors()->add('attachment', 'Un message vocal ne doit pas dépasser '.intdiv($limit, 1024).' Mo.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required_without' => 'Écrivez un message ou joignez un fichier.',
            'attachment.extensions' => 'Ce type de fichier n\'est pas accepté. Envoyez un PDF, une photo, un document Office ou un message vocal.',
            'attachment.max' => 'Le fichier est trop lourd.',
        ];
    }
}
