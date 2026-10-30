<?php

namespace App\Models;

use App\Enums\OpportunityKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'title', 'organisation', 'kind', 'summary', 'criteria', 'stages', 'sectors', 'max_amount_fcfa', 'amount', 'deadline',
    'deadline_note', 'url', 'source_label', 'checked_on', 'is_published', 'accepts_applications', 'application_questions',
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
            'accepts_applications' => 'boolean',
            'application_questions' => 'array',
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

    /**
     * Organisation vérifiée qui a publié le programme (OR-01), sinon l'équipe IDEE.
     *
     * @return BelongsTo<Organisation, $this>
     */
    public function organisationPage(): BelongsTo
    {
        return $this->belongsTo(Organisation::class, 'organisation_id');
    }

    /**
     * Candidatures reçues depuis IDEE (OR-02).
     *
     * @return HasMany<ProgrammeApplication, $this>
     */
    public function programmeApplications(): HasMany
    {
        return $this->hasMany(ProgrammeApplication::class)->latest();
    }

    /**
     * Le programme reçoit des candidatures depuis IDEE et n'est pas clos.
     */
    public function takesApplications(): bool
    {
        return $this->accepts_applications && $this->organisation_id !== null && $this->is_published && ! $this->isClosed();
    }

    public function isClosed(): bool
    {
        return $this->deadline !== null && $this->deadline->lt(today());
    }
}
