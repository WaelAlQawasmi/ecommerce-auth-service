<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Persist a new user.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * Assign a role to the user without detaching existing ones.
     */
    public function assignRole(User $user, Role $role): void;

    /**
     * Soft delete the given user.
     */
    public function softDelete(User $user): bool;
}
