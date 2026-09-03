<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentIssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'document_analysis_id' => $this->document_analysis_id,
            'detected_element_id' => $this->detected_element_id,
            'source' => $this->source->value,
            'category' => $this->category->value,
            'severity' => $this->severity,
            'description' => $this->description,
            'recommendation' => $this->recommendation,
            'location' => $this->location,
            'decision' => $this->decision->value,
            'ignored_reason' => $this->ignored_reason,
            'review_mode' => $this->review_mode?->value,
            'probabilistic' => $this->probabilistic,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
