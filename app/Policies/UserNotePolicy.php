<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNote;

// Data pribadi hanya untuk pemiliknya (blueprint §10.3, uji IDOR). @2026-Alifiandra
class UserNotePolicy
{
    public function view(User $user, UserNote $item): bool
    {
        return $item->user_id === $user->id;
    }

    public function update(User $user, UserNote $item): bool
    {
        return $item->user_id === $user->id;
    }

    public function delete(User $user, UserNote $item): bool
    {
        return $item->user_id === $user->id;
    }
}
