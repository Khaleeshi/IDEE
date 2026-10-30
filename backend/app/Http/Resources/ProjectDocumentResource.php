<?php

namespace App\Http\Resources;

use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProjectDocument
 */
class ProjectDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'file_name' => $this->original_name,
            'mime' => $this->mime,
            'size' => $this->size,
            'url' => url("/api/documents/{$this->id}"),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
