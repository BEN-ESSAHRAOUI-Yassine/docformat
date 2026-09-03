<?php

namespace App\Services\Reversa;

use App\Enums\ActionType;
use App\Models\BibliographyEntry;
use App\Models\Citation;
use App\Models\DetectedElement;
use App\Models\DocumentAction;
use App\Models\DocumentIssue;
use App\Models\StyleViolation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ElementStateReversor implements Reversor
{
    private const HANDLED = [
        ActionType::HeadingAssigned,
        ActionType::StyleFixed,
        ActionType::CaptionAdded,
        ActionType::CitationLinked,
        ActionType::Renumbered,
        ActionType::PageBreakAdded,
        ActionType::PageBreakRemoved,
    ];

    public function canHandle(DocumentAction $action): bool
    {
        $type = $action->action_type;

        return is_string($type)
            ? in_array($type, array_map(fn ($c) => $c->value, self::HANDLED))
            : in_array($type, self::HANDLED);
    }

    public function reverse(DocumentAction $action): mixed
    {
        return $this->restore($action, 'old_value');
    }

    public function apply(DocumentAction $action): mixed
    {
        return $this->restore($action, 'new_value');
    }

    private function restore(DocumentAction $action, string $valueKey): mixed
    {
        $snapshot = $action->{$valueKey};

        if (! is_array($snapshot) || ! isset($snapshot['model'], $snapshot['id'], $snapshot['attributes'])) {
            return null;
        }

        $model = $this->resolveModel($snapshot['model']);

        if (! $model || ! class_exists($model)) {
            return null;
        }

        return DB::transaction(function () use ($model, $snapshot) {
            $record = (new $model)->find($snapshot['id']);

            if (! $record) {
                return null;
            }

            $attributes = $snapshot['attributes'];
            $record->update($this->castAttributes($model, $attributes));

            return $record;
        });
    }

    /**
     * @return class-string<Model>|null
     */
    private function resolveModel(string $name): ?string
    {
        $map = [
            'DetectedElement' => DetectedElement::class,
            'Citation' => Citation::class,
            'BibliographyEntry' => BibliographyEntry::class,
            'DocumentIssue' => DocumentIssue::class,
            'StyleViolation' => StyleViolation::class,
        ];

        return $map[$name] ?? null;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function castAttributes(string $model, array $attributes): array
    {
        $instance = new $model;

        return $instance->getCasts()
            ? collect($attributes)->mapWithKeys(function ($value, $key) {
                // JSON columns are already array-typed payloads; leave them as-is.
                return [$key => $value];
            })->all()
            : $attributes;
    }
}
