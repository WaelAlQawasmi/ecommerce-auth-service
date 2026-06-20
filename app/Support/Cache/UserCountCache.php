<?php

namespace App\Support\Cache;

use Illuminate\Support\Facades\Cache;

final class UserCountCache
{
    public const KEY = 'users.count';

    public const TTL_SECONDS = 3600;

    /**
     * @param  callable(): int  $callback
     */
    public static function remember(callable $callback): int
    {
        return (int) Cache::remember(self::KEY, self::TTL_SECONDS, $callback);
    }

    public static function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
