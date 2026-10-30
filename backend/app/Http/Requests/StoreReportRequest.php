<?php

namespace App\Http\Requests;

use App\Enums\ReportReason;
use App\Models\GroupPost;
use App\Models\Message;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    private const TARGETS = ['membre' => User::class, 'projet' => Project::class, 'message' => Message::class, 'organisation' => Organisation::class, 'publication' => GroupPost::class];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(array_keys(self::TARGETS))],
            'target_id' => ['required', 'string', 'size:26'],
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['reason.required' => 'Choisissez le motif du signalement.'];
    }

    public function target(): User|Project|Message|Organisation|GroupPost
    {
        return self::TARGETS[$this->validated('target_type')]::findOrFail($this->validated('target_id'));
    }
}
