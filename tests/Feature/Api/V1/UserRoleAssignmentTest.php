<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleSlug;
use App\Models\User;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    public function test_admin_can_assign_any_role(): void
    {
        $this->actingAsRole(RoleSlug::Admin);
        $user = User::factory()->create();

        $this->postJson("/api/v1/users/{$user->id}/roles", ['role' => RoleSlug::Support->value])
            ->assertOk()
            ->assertJsonPath('data.roles.0.slug', RoleSlug::Support->value);

        $this->postJson("/api/v1/users/{$user->id}/roles", ['role' => RoleSlug::Admin->value])
            ->assertOk();
    }

    public function test_support_can_assign_customer_role(): void
    {
        $this->actingAsRole(RoleSlug::Support);
        $user = User::factory()->create();

        $this->postJson("/api/v1/users/{$user->id}/roles", ['role' => RoleSlug::Customer->value])
            ->assertOk()
            ->assertJsonPath('data.roles.0.slug', RoleSlug::Customer->value);
    }

    public function test_support_cannot_assign_non_customer_roles(): void
    {
        $this->actingAsRole(RoleSlug::Support);
        $user = User::factory()->create();

        $this->postJson("/api/v1/users/{$user->id}/roles", ['role' => RoleSlug::Admin->value])
            ->assertForbidden()
            ->assertJsonPath('message', 'Only administrators can assign non-customer roles.');

        $this->postJson("/api/v1/users/{$user->id}/roles", ['role' => RoleSlug::Support->value])
            ->assertForbidden();
    }

    public function test_customer_cannot_assign_roles(): void
    {
        $this->actingAsRole(RoleSlug::Customer);
        $user = User::factory()->create();

        $this->postJson("/api/v1/users/{$user->id}/roles", ['role' => RoleSlug::Customer->value])
            ->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_public_registration_rejects_non_customer_role(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Bad Actor',
            'email' => 'bad@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => RoleSlug::Admin->value,
        ])->assertForbidden();
    }
}
