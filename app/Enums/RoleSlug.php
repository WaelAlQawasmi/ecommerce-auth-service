<?php

namespace App\Enums;

enum RoleSlug: string
{
    case Admin = 'admin';
    case Support = 'support';
    case Customer = 'customer';

    /**
     * Human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Support => 'Support',
            self::Customer => 'Customer',
        };
    }

    /**
     * Role slugs allowed to access staff-only user management endpoints.
     *
     * @return array<int, string>
     */
    public static function staffSlugs(): array
    {
        return [
            self::Admin->value,
            self::Support->value,
        ];
    }
}
