<?php

namespace App\Http\Requests;

use App\Enums\NeedType;
use App\Enums\ProjectStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * Création guidée (PR-01) : le problème et la solution forment le résumé public.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('summary')) && ($this->filled('problem') || $this->filled('solution'))) {
            $this->merge(['summary' => trim($this->input('problem')."\n\n".$this->input('solution'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'summary' => ['required', 'string', 'min:30', 'max:2000'],
            'details' => ['nullable', 'string', 'max:20000'],
            'sector_id' => ['nullable', 'integer', Rule::exists('sectors', 'id')],
            'region' => ['nullable', 'string', 'max:80'],
            'stage' => ['nullable', Rule::enum(ProjectStage::class)],
            // Premier appel à action, facultatif, publié avec le projet.
            'first_need_type' => ['nullable', Rule::enum(NeedType::class)],
            'first_need_description' => ['nullable', 'required_with:first_need_type', 'string', 'min:30', 'max:5000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function projectAttributes(): array
    {
        return collect($this->validated())->except(['first_need_type', 'first_need_description'])->all();
    }

    /**
     * @return array{type: string, title: string, description: string}|null
     */
    public function firstNeed(): ?array
    {
        if (! $this->filled('first_need_type')) {
            return null;
        }

        $description = $this->validated('first_need_description');

        return [
            'type' => $this->validated('first_need_type'),
            'title' => Str::limit(Str::before($description, "\n"), 80),
            'description' => $description,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Donnez un titre à votre projet.',
            'summary.required' => 'Décrivez votre projet en quelques phrases.',
            'summary.min' => 'Le résumé doit contenir au moins 30 caractères.',
            'first_need_description.required_with' => 'Précisez en quelques mots ce dont vous avez besoin.',
            'first_need_description.min' => 'Décrivez ce besoin en au moins 30 caractères.',
        ];
    }
}
