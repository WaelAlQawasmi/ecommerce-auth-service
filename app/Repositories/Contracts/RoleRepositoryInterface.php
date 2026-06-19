<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

interface RoleRepositoryInterface
{
    /**
     * @return Collection<int, Role>
     */
    public function all(): Collection;

    public function findById(int $id): ?Role;

    public function findBySlug(string $slug): ?Role;
}
