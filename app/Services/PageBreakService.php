<?php

namespace App\Services;

use App\Enums\ActionOrigin;
use App\Enums\ActionType;
use App\Enums\Reversibility;
use App\Models\DetectedElement;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;

class PageBreakService
{
    /**
     * Insert a user page break before a target element.
     */
    public function insertBefore(Document $document, DetectedElement $target, string $context): DetectedElement
    {
        $analysis = $target->documentAnalysis ?? $document->latestAnalysis;

        $elementIndex = $target->element_index;
        $newIndex = $elementIndex - 0.5;

        $element = DetectedElement::updateOrCreate(
            [
                'document_analysis_id' => $analysis?->id,
                'document_id' => $document->id,
                'type' => 'page_break',
                'element_index' => $newIndex,
            ],
            [
                'content' => null,
                'metadata' => [
                    'origin' => 'user',
                    'context' => $context,
                    'before_element_id' => $target->id,
                    'before_element_index' => $target->element_index,
                ],
            ]
        );

        app(ActionLogger::class)->record($document, [
            'action_type' => ActionType::PageBreakAdded,
            'element_type' => 'DetectedElement',
            'element_id' => $element->id,
            'origin' => ActionOrigin::Manual,
            'user_id' => Auth::id(),
            'old_value' => ['context' => $context, 'target_id' => $target->id],
            'new_value' => ['element_id' => $element->id],
            'reversibility' => Reversibility::Full,
        ]);

        return $element;
    }

    /**
     * Remove a user-inserted page break.
     */
    public function remove(Document $document, DetectedElement $element): bool
    {
        if ($element->type !== 'page_break') {
            return false;
        }

        $isUserBreak = ($element->metadata['origin'] ?? null) === 'user';

        if (! $isUserBreak) {
            return false;
        }

        app(ActionLogger::class)->record($document, [
            'action_type' => ActionType::PageBreakRemoved,
            'element_type' => 'DetectedElement',
            'element_id' => $element->id,
            'origin' => ActionOrigin::Manual,
            'user_id' => Auth::id(),
            'reversibility' => Reversibility::Full,
        ]);

        return (bool) $element->delete();
    }
}
