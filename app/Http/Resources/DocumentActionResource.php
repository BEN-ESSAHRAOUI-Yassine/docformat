<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'user_id' => $this->user_id,
            'action_type' => $this->action_type,
            'element_type' => $this->element_type,
            'element_id' => $this->element_id,
            'origin' => $this->origin->value,
            'reversibility' => $this->reversibility->value,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'payload' => $this->payload,
            'bulk_id' => $this->bulk_id,
            'is_reversible' => $this->isReversible(),
            'user' => $this->whenLoaded('user'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
