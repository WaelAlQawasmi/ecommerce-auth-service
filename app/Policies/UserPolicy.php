<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\User;

class UserPolicy
{
    /**
     * Staff may assign the customer role; only admins may assign other roles.
     */
    public function assignRole(User $authUser, User $target, string $roleSlug): bool
    {
        if ($roleSlug === RoleSlug::Customer->value) {
            return $authUser->hasAnyRole(RoleSlug::staffSlugs());
        }

        return $authUser->hasRole(RoleSlug::Admin->value);
    }

    /**
     * Only administrators may create users with non-customer roles.
     */
    public function createWithRole(?User $authUser, string $roleSlug): bool
    {
        if ($roleSlug === RoleSlug::Customer->value) {
            return true;
        }

        return $authUser !== null && $authUser->hasRole(RoleSlug::Admin->value);
    }

    /**
     * A user may delete their own account; admins may delete any account.
     */
    public function delete(User $authUser, User $target): bool
    {
        return $authUser->getKey() === $target->getKey()
            || $authUser->hasRole(RoleSlug::Admin->value);
    }
}
