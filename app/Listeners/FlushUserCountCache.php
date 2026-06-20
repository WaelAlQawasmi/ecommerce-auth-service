<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Support\Cache\UserCountCache;

class FlushUserCountCache
{
    public function handle(UserCreated|UserDeleted $event): void
    {
        UserCountCache::forget();
    }
}
