<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\Cache\UserCountCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
    ) {}

    /**
     * Paginate all users.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginate($perPage);
    }

    /**
     * Search users by partial email match.
     *
     * @return Collection<int, User>
     */
    public function searchByEmail(string $email): Collection
    {
        return $this->users->searchByEmail(strtolower(trim($email)));
    }

    /**
     * Count active users (cached; invalidated on create/delete).
     */
    public function count(): int
    {
        return UserCountCache::remember(fn (): int => $this->users->count());
    }

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
