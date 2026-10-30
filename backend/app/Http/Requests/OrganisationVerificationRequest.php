<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CF-03 : numéros RCCM et NINEA, avec l'extrait ou l'attestation correspondante.
 */
class OrganisationVerificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rccm' => ['required', 'string', 'max:50', 'regex:/^SN[\s\-.]*[A-Z]{3}[\s\-.]*\d{4}[\s\-.]*[A-Z][\s\-.]*\d{1,6}$/i'],
            'ninea' => ['required', 'string', 'max:30', 'regex:/^[0-9][0-9A-Z\s]{6,20}$/i'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('idee.documents.max_kb')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rccm.required' => 'Indiquez le numéro RCCM.',
            'rccm.regex' => 'Le numéro RCCM ressemble à « SN-DKR-2024-B-12345 ».',
            'ninea.required' => 'Indiquez le NINEA.',
            'ninea.regex' => 'Le NINEA commence par des chiffres, par exemple « 009876543 2V2 ».',
            'document.required' => 'Joignez l\'extrait RCCM ou l\'attestation NINEA.',
            'document.mimes' => 'Le justificatif doit être un PDF ou une photo (JPG, PNG).',
            'document.max' => 'Le justificatif est trop lourd.',
        ];
    }
}
