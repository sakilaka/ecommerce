<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class CommerceAdminPolicy
{
    public function access(User $user): bool
    {
        return $user->role === UserRole::Admin && $user->is_active;
    }
}
