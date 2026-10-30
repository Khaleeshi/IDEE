<?php

namespace App\Models;

use App\Enums\NeedStatus;
use App\Enums\ProjectStage;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'summary', 'details', 'sector_id', 'region', 'stage'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => ProjectStage::class,
            'status' => ProjectStatus::class,
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * @return HasMany<Need, $this>
     */
    public function needs(): HasMany
    {
        return $this->hasMany(Need::class);
    }

    /**
     * @return HasMany<Need, $this>
     */
    public function openNeeds(): HasMany
    {
        return $this->needs()->where('status', NeedStatus::Ouvert);
    }

    /**
     * @return HasMany<ProjectVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ProjectVersion::class);
    }

    /**
     * Journal de progression, du plus récent au plus ancien (PR-04).
     *
     * @return HasMany<ProjectUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->latest()->latest('id');
    }

    /**
     * Projets visibles dans le fil et les recommandations (RG-07).
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ProjectStatus::Actif);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->owner_id === $user->getKey();
    }

    /**
     * Enregistre une mise à jour : le projet redevient actif s'il était en sommeil.
     */
    public function recordActivity(): void
    {
        $this->last_activity_at = now();

        if ($this->status === ProjectStatus::EnSommeil) {
            $this->status = ProjectStatus::Actif;
        }

        $this->save();
    }
}
