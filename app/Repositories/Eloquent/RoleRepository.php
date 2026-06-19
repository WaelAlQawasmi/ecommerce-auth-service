<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * @return Collection<int, Role>
     */
    public function all(): Collection
    {
        return Role::orderBy('name')->get();
    }

    public function findById(int $id): ?Role
    {
        return Role::find($id);
    }

    public function findBySlug(string $slug): ?Role
    {
        return Role::where('slug', $slug)->first();
    }
}
