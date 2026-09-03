<?php

namespace App\Services;

use App\Enums\ActionOrigin;
use App\Enums\Reversibility;
use App\Models\Document;
use App\Models\DocumentAction;
use Illuminate\Support\Facades\Auth;

class ActionLogger
{
    public function record(Document $document, array $data): DocumentAction
    {
        $origin = $data['origin'] ?? ActionOrigin::Automatic;

        return DocumentAction::create([
            'document_id' => $document->id,
            'user_id' => $data['user_id'] ?? Auth::id(),
            'action_type' => $data['action_type'],
            'element_type' => $data['element_type'] ?? null,
            'element_id' => $data['element_id'] ?? null,
            'origin' => $origin instanceof ActionOrigin ? $origin->value : $origin,
            'old_value' => $data['old_value'] ?? null,
            'new_value' => $data['new_value'] ?? null,
            'payload' => $data['payload'] ?? null,
            'reversibility' => $data['reversibility'] ?? Reversibility::Full,
            'bulk_id' => $data['bulk_id'] ?? null,
        ]);
    }

    public function recordExternal(Document $document, array $data): DocumentAction
    {
        return $this->record($document, array_merge([
            'origin' => ActionOrigin::Automatic,
            'reversibility' => Reversibility::None,
        ], $data));
    }
}
