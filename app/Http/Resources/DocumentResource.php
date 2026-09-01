<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'original_filename' => $this->original_filename,
            'project_id' => $this->project_id,
            'status' => $this->status->value,
            'file_hash' => $this->file_hash,
            'current_version' => new DocumentVersionResource($this->whenLoaded('currentVersion')),
            'versions_count' => $this->whenCounted('versions'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
