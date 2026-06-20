<?php

namespace Tests\Unit\Enums;

use App\Enums\RoleSlug;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RoleSlugTest extends TestCase
{
    #[Test]
    public function it_exposes_all_application_roles(): void
    {
        $this->assertSame('admin', RoleSlug::Admin->value);
        $this->assertSame('support', RoleSlug::Support->value);
        $this->assertSame('customer', RoleSlug::Customer->value);
    }

    #[Test]
    public function it_returns_human_readable_labels(): void
    {
        $this->assertSame('Administrator', RoleSlug::Admin->label());
        $this->assertSame('Support', RoleSlug::Support->label());
        $this->assertSame('Customer', RoleSlug::Customer->label());
    }

    #[Test]
    public function it_identifies_staff_roles_for_user_management(): void
    {
        $this->assertSame(['admin', 'support'], RoleSlug::staffSlugs());
    }
}
