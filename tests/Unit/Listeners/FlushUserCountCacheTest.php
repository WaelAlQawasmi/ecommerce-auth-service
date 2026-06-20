<?php

namespace Tests\Unit\Listeners;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Listeners\FlushUserCountCache;
use App\Models\User;
use App\Support\Cache\UserCountCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FlushUserCountCacheTest extends TestCase
{
    public function test_it_forgets_user_count_cache_on_user_created(): void
    {
        Cache::put(UserCountCache::KEY, 42, UserCountCache::TTL_SECONDS);

        (new FlushUserCountCache)->handle(new UserCreated(User::factory()->make()));

        $this->assertFalse(Cache::has(UserCountCache::KEY));
    }

    public function test_it_forgets_user_count_cache_on_user_deleted(): void
    {
        Cache::put(UserCountCache::KEY, 42, UserCountCache::TTL_SECONDS);

        (new FlushUserCountCache)->handle(new UserDeleted(User::factory()->make()));

        $this->assertFalse(Cache::has(UserCountCache::KEY));
    }
}
