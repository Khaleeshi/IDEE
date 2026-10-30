<?php

namespace App\Http\Requests;

use App\Enums\NeedStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseNeedRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(NeedStatus::class)->except([NeedStatus::Ouvert])],
            'outcome' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
