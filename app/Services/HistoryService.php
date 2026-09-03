<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAction;
use App\Services\Reversa\ElementStateReversor;
use App\Services\Reversa\MergeReversor;
use App\Services\Reversa\Reversor;
use Illuminate\Support\Collection;

class HistoryService
{
    public const MAX_DEPTH = 50;

    /**
     * @var array<int, Reversor>
     */
    private array $reversors;

    public function __construct(?array $reversors = null)
    {
        $this->reversors = $reversors ?? [
            new ElementStateReversor,
            new MergeReversor,
        ];
    }

    public function undo(Document $document): ?DocumentAction
    {
        $action = $this->latestAppliedAction($document);

        if (! $action) {
            return null;
        }

        $this->run($action, 'reverse');

        $action->update(['undone_at' => now()]);

        return $action;
    }

    public function redo(Document $document): ?DocumentAction
    {
        $action = DocumentAction::forDocument($document->id)->undone()->orderByDesc('id')->first();

        if (! $action) {
            return null;
        }

        $this->run($action, 'apply');

        $action->update(['undone_at' => null]);

        return $action;
    }

    public function undoBulk(Document $document, string $bulkId): Collection
    {
        $actions = DocumentAction::forDocument($document->id)->inBulk($bulkId)->orderByDesc('id')->get();

        foreach ($actions as $action) {
            if ($action->isReversible() && ! $action->undone_at) {
                $this->run($action, 'reverse');
                $action->update(['undone_at' => now()]);
            }
        }

        return $actions;
    }

    private function latestAppliedAction(Document $document): ?DocumentAction
    {
        return DocumentAction::forDocument($document->id)
            ->reversible()
            ->notUndone()
            ->orderByDesc('id')
            ->first();
    }

    private function run(DocumentAction $action, string $direction): void
    {
        foreach ($this->reversors as $reversor) {
            if (! $reversor->canHandle($action)) {
                continue;
            }

            if ($direction === 'reverse') {
                $reversor->reverse($action);
            } else {
                $reversor->apply($action);
            }

            return;
        }
    }
}
