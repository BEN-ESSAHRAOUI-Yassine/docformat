<?php

namespace App\Services\Reversa;

use App\Models\DocumentAction;

interface Reversor
{
    public function canHandle(DocumentAction $action): bool;

    /**
     * @return mixed nil on success, or a snapshot that could be used by a reversor to un-reverse.
     */
    public function reverse(DocumentAction $action): mixed;

    /**
     * Re-apply the action's new value (used for redo).
     */
    public function apply(DocumentAction $action): mixed;
}
