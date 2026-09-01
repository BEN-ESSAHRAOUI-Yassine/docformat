<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class AnalysisPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $user->id === $document->project->owner_id;
    }

    public function trigger(User $user, Document $document): bool
    {
        return $user->id === $document->project->owner_id;
    }
}
