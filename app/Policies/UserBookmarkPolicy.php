<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserBookmark;

// Data pribadi hanya untuk pemiliknya (blueprint §10.3, uji IDOR). @2026-Alifiandra
class UserBookmarkPolicy
{
    public function view(User $user, UserBookmark $item): bool
    {
        return $item->user_id === $user->id;
    }

    public function update(User $user, UserBookmark $item): bool
    {
        return $item->user_id === $user->id;
    }

    public function delete(User $user, UserBookmark $item): bool
    {
        return $item->user_id === $user->id;
    }
}
