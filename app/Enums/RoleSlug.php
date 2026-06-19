<?php

namespace App\Enums;

enum RoleSlug: string
{
    case Admin = 'admin';
    case User = 'user';

    /**
     * Human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::User => 'User',
        };
    }
}
