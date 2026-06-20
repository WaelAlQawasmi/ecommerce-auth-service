<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleSlug;
use App\Models\User;
use App\Support\Cache\UserCountCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_admin_can_list_users_with_pagination(): void
    {
        $this->actingAsRole(RoleSlug::Admin);
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/users?per_page=2');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 4)
            ->assertJsonPath('meta.pagination.per_page', 2)
            ->assertJsonPath('meta.pagination.current_page', 1);
    }

    public function test_support_can_list_users(): void
    {
        $this->actingAsRole(RoleSlug::Support);
        User::factory()->count(2)->create();

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_customer_cannot_list_users(): void
    {
        $this->actingAsRole(RoleSlug::Customer);

        $this->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_unauthenticated_user_cannot_list_users(): void
    {
        $this->getJson('/api/v1/users')
            ->assertUnauthorized();
    }

    public function test_admin_can_search_users_by_email(): void
    {
        $this->actingAsRole(RoleSlug::Admin);
        User::factory()->create(['email' => 'search.me@example.com']);
        User::factory()->create(['email' => 'other@example.com']);

        $response = $this->getJson('/api/v1/users/search?email=search.me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'search.me@example.com')
            ->assertJsonPath('meta.count', 1);
    }

    public function test_support_can_search_users_by_email(): void
    {
        $this->actingAsRole(RoleSlug::Support);
        User::factory()->create(['email' => 'support.find@example.com']);

        $this->getJson('/api/v1/users/search?email=support.find')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_customer_cannot_search_users_by_email(): void
    {
        $this->actingAsRole(RoleSlug::Customer);

        $this->getJson('/api/v1/users/search?email=test')
            ->assertForbidden();
    }

    public function test_search_requires_email_parameter(): void
    {
        $this->actingAsRole(RoleSlug::Admin);

        $this->getJson('/api/v1/users/search')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_can_get_user_count(): void
    {
        $this->actingAsRole(RoleSlug::Admin);
        User::factory()->count(2)->create();

        $this->getJson('/api/v1/users/count')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 3);
    }

    public function test_support_can_get_user_count(): void
    {
        $this->actingAsRole(RoleSlug::Support);
        User::factory()->count(1)->create();

        $this->getJson('/api/v1/users/count')
            ->assertOk()
            ->assertJsonPath('data.count', 2);
    }

    public function test_customer_cannot_get_user_count(): void
    {
        $this->actingAsRole(RoleSlug::Customer);

        $this->getJson('/api/v1/users/count')
            ->assertForbidden();
    }

    public function test_user_count_excludes_soft_deleted_users(): void
    {
        $this->actingAsRole(RoleSlug::Admin);
        User::factory()->create()->delete();

        $this->getJson('/api/v1/users/count')
            ->assertOk()
            ->assertJsonPath('data.count', 1);
    }

    public function test_user_count_is_read_from_cache(): void
    {
        Cache::flush();
        $this->actingAsRole(RoleSlug::Admin);

        $this->getJson('/api/v1/users/count')
            ->assertOk()
            ->assertJsonPath('data.count', 1);

        Cache::put(UserCountCache::KEY, 999, UserCountCache::TTL_SECONDS);

        $this->getJson('/api/v1/users/count')
            ->assertOk()
            ->assertJsonPath('data.count', 999);
    }

    public function test_user_count_cache_is_flushed_when_user_is_deleted(): void
    {
        Cache::flush();
        $this->actingAsRole(RoleSlug::Admin);
        $user = User::factory()->create();

        $this->getJson('/api/v1/users/count')
            ->assertOk()
            ->assertJsonPath('data.count', 2);

        Cache::put(UserCountCache::KEY, 999, UserCountCache::TTL_SECONDS);

        $this->deleteJson("/api/v1/users/{$user->id}")
            ->assertOk();

        $this->getJson('/api/v1/users/count')
            ->assertOk()
            ->assertJsonPath('data.count', 1);
    }

    public function test_user_count_cache_is_flushed_when_user_is_created(): void
    {
        Cache::flush();
        $this->actingAsRole(RoleSlug::Admin);

        $this->getJson('/api/v1/users/count')
            ->assertOk()
            ->assertJsonPath('data.count', 1);

        Cache::put(UserCountCache::KEY, 999, UserCountCache::TTL_SECONDS);

        User::factory()->create();

        $this->getJson('/api/v1/users/count')
            ->assertOk()
            ->assertJsonPath('data.count', 2);
    }
}
