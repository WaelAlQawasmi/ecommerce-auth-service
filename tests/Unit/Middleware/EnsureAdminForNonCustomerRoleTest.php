<?php

namespace Tests\Unit\Middleware;

use App\Enums\RoleSlug;
use App\Http\Middleware\EnsureAdminForNonCustomerRole;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnsureAdminForNonCustomerRoleTest extends TestCase
{
    public function test_it_allows_customer_role_without_admin(): void
    {
        $support = User::factory()->withRole(RoleSlug::Support)->create();
        $request = Request::create('/api/v1/users/1/roles', 'POST', ['role' => RoleSlug::Customer->value]);
        $request->setUserResolver(fn () => $support);

        $response = (new EnsureAdminForNonCustomerRole)->handle($request, fn () => ApiResponse::success());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_blocks_non_customer_role_for_support_user(): void
    {
        $support = User::factory()->withRole(RoleSlug::Support)->create();
        $request = Request::create('/api/v1/users/1/roles', 'POST', ['role' => RoleSlug::Admin->value]);
        $request->setUserResolver(fn () => $support);

        $response = (new EnsureAdminForNonCustomerRole)->handle($request, fn () => ApiResponse::success());

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(
            'Only administrators can assign non-customer roles.',
            $response->getData(true)['message'],
        );
    }

    public function test_it_allows_non_customer_role_for_admin_user(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $request = Request::create('/api/v1/users/1/roles', 'POST', ['role' => RoleSlug::Support->value]);
        $request->setUserResolver(fn () => $admin);

        $response = (new EnsureAdminForNonCustomerRole)->handle($request, fn () => ApiResponse::success());

        $this->assertSame(200, $response->getStatusCode());
    }
}
