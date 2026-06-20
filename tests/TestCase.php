<?php

namespace Tests;

use App\Enums\RoleSlug;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function actingAsRole(RoleSlug $role): User
    {
        $user = User::factory()->withRole($role)->create();
        Passport::actingAs($user);

        return $user;
    }
}
