<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Project;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->id === $project->owner_id;
    }

    public function view(User $user, Document $document): bool
    {
        return $user->id === $document->project->owner_id;
    }

    public function create(User $user, Project $project): bool
    {
        return $user->id === $project->owner_id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->id === $document->project->owner_id;
    }

    public function trigger(User $user, Document $document): bool
    {
        return $user->id === $document->project->owner_id;
    }
}
