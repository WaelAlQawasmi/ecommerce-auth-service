<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function assignRole(User $user, Role $role): void
    {
        $user->roles()->syncWithoutDetaching([$role->getKey()]);
    }

    public function softDelete(User $user): bool
    {
        return (bool) $user->delete();
    }
}
