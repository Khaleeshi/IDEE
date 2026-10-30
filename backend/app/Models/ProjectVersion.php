<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Version horodatée d'un projet, scellée par une empreinte SHA-256.
 * Elle prouve l'antériorité d'un texte, sans constituer un titre de
 * propriété intellectuelle (cahier des charges, section 8).
 */
#[Fillable(['content', 'fingerprint'])]
class ProjectVersion extends Model
{
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
