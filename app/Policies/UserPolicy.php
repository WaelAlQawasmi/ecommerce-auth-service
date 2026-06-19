<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\User;

class UserPolicy
{
    /**
     * A user may delete their own account; admins may delete any account.
     */
    public function delete(User $authUser, User $target): bool
    {
        return $authUser->getKey() === $target->getKey()
            || $authUser->hasRole(RoleSlug::Admin->value);
    }
}
