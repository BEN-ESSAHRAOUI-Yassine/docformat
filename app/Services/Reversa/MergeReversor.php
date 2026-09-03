<?php

namespace App\Services\Reversa;

use App\Enums\ActionType;
use App\Models\BibliographyEntry;
use App\Models\DocumentAction;
use Illuminate\Support\Facades\DB;

class MergeReversor implements Reversor
{
    public function canHandle(DocumentAction $action): bool
    {
        return $action->action_type === ActionType::Merged->value;
    }

    public function reverse(DocumentAction $action): mixed
    {
        $payload = $action->payload;

        if (! is_array($payload) || ! isset($payload['deleted_entry'])) {
            return null;
        }

        $entryData = $payload['deleted_entry'];
        $entryData['document_id'] = $action->document_id;

        return DB::transaction(function () use ($entryData) {
            return BibliographyEntry::create($entryData);
        });
    }

    public function apply(DocumentAction $action): mixed
    {
        $payload = $action->payload;

        if (! is_array($payload) || ! isset($payload['deleted_entry'])) {
            return null;
        }

        $deletedId = $payload['deleted_entry']['id'] ?? null;

        if (! $deletedId) {
            return null;
        }

        return DB::transaction(function () use ($deletedId) {
            $entry = BibliographyEntry::find($deletedId);

            if ($entry) {
                $entry->delete();
            }

            return $entry;
        });
    }
}
