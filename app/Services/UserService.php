<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
    ) {}

    /**
     * Assign a role (by slug) to the given user.
     *
     * @throws ValidationException
     */
    public function assignRole(User $user, string $roleSlug): User
    {
        $role = $this->roles->findBySlug($roleSlug);

        if ($role === null) {
            throw ValidationException::withMessages([
                'role' => ['The selected role is invalid.'],
            ]);
        }

        $this->users->assignRole($user, $role);

        return $user->load('roles');
    }

    /**
     * Soft delete the given user account.
     */
    public function deleteAccount(User $user): void
    {
        $this->users->softDelete($user);
    }
}
