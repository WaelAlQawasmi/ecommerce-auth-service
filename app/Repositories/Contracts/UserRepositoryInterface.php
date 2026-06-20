<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
     * Paginate users with roles loaded.
     */
    public function paginate(int $perPage): LengthAwarePaginator;

    /**
     * Search users by partial email match.
     *
     * @return Collection<int, User>
     */
    public function searchByEmail(string $email): Collection;

    /**
     * Count active (non soft-deleted) users.
     */
    public function count(): int;

    /**
     * Assign a role to the user without detaching existing ones.
     */
    public function assignRole(User $user, Role $role): void;

    /**
     * Soft delete the given user.
     */
    public function softDelete(User $user): bool;
}
