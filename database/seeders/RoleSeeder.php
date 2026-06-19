<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [RoleSlug::Admin, 'Administrator', 'Full administrative access.'],
            [RoleSlug::User, 'User', 'Standard application user.'],
        ];

        foreach ($roles as [$slug, $name, $description]) {
            Role::updateOrCreate(
                ['slug' => $slug->value],
                ['name' => $name, 'description' => $description],
            );
        }
    }
}
