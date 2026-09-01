<?php

namespace App\Policies;

use App\Models\StyleProfile;
use App\Models\User;

class StyleProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StyleProfile $profile): bool
    {
        return $profile->is_system || $profile->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, StyleProfile $profile): bool
    {
        return ! $profile->is_system && $profile->user_id === $user->id;
    }

    public function delete(User $user, StyleProfile $profile): bool
    {
        return ! $profile->is_system && $profile->user_id === $user->id;
    }
}
