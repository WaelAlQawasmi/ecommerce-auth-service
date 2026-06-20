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
            [RoleSlug::Support, 'Support', 'Support staff with read access to user data.'],
            [RoleSlug::Customer, 'Customer', 'Standard customer account.'],
        ];

        foreach ($roles as [$slug, $name, $description]) {
            Role::updateOrCreate(
                ['slug' => $slug->value],
                ['name' => $name, 'description' => $description],
            );
        }
    }
}
