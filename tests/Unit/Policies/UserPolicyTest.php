<?php

namespace Tests\Unit\Policies;

use App\Enums\RoleSlug;
use App\Models\User;
use App\Policies\UserPolicy;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy;
    }

    public function test_admin_can_assign_non_customer_roles(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $target = User::factory()->create();

        $this->assertTrue($this->policy->assignRole($admin, $target, RoleSlug::Support->value));
        $this->assertTrue($this->policy->assignRole($admin, $target, RoleSlug::Admin->value));
    }

    public function test_support_can_assign_customer_role_only(): void
    {
        $support = User::factory()->withRole(RoleSlug::Support)->create();
        $target = User::factory()->create();

        $this->assertTrue($this->policy->assignRole($support, $target, RoleSlug::Customer->value));
        $this->assertFalse($this->policy->assignRole($support, $target, RoleSlug::Admin->value));
        $this->assertFalse($this->policy->assignRole($support, $target, RoleSlug::Support->value));
    }

    public function test_only_admin_can_create_users_with_non_customer_roles(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $support = User::factory()->withRole(RoleSlug::Support)->create();

        $this->assertTrue($this->policy->createWithRole($admin, RoleSlug::Support->value));
        $this->assertFalse($this->policy->createWithRole($support, RoleSlug::Support->value));
        $this->assertTrue($this->policy->createWithRole(null, RoleSlug::Customer->value));
    }
}
