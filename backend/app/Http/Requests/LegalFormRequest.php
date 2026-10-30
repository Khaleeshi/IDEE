<?php

namespace App\Http\Requests;

use App\Services\Journeys\LegalFormAdvisor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LegalFormRequest extends FormRequest
{
    /**
     * Sans réponse, la page affiche les questions ; avec une réponse, les trois sont exigées.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $names = array_keys(LegalFormAdvisor::QUESTIONS);

        return collect(LegalFormAdvisor::QUESTIONS)->mapWithKeys(fn (array $question, string $name) => [$name => [
            'nullable',
            'required_with:'.implode(',', array_diff($names, [$name])),
            Rule::in(array_keys($question['options'])),
        ]])->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['required_with' => 'Répondez aux trois questions pour obtenir une orientation.'];
    }

    /**
     * @return array{associes: string, protection: string, taille: string}|null
     */
    public function answers(): ?array
    {
        $answers = $this->validated();

        return count(array_filter($answers)) === count(LegalFormAdvisor::QUESTIONS) ? $answers : null;
    }
}
