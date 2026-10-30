<?php

namespace App\Models;

use App\Enums\OpportunityKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'slug', 'title', 'organisation', 'kind', 'summary', 'criteria', 'stages', 'sectors', 'max_amount_fcfa', 'amount', 'deadline',
    'deadline_note', 'url', 'source_label', 'checked_on', 'is_published',
])]
class Opportunity extends Model
{
    use HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => OpportunityKind::class,
            'stages' => 'array',
            'sectors' => 'array',
            'max_amount_fcfa' => 'integer',
            'deadline' => 'date',
            'checked_on' => 'date',
            'is_published' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    /**
     * Opportunités publiées et encore ouvertes.
     *
     * @param  Builder<Opportunity>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('deadline')->orWhereDate('deadline', '>=', today()));
    }

    public function isClosed(): bool
    {
        return $this->deadline !== null && $this->deadline->lt(today());
    }
}
