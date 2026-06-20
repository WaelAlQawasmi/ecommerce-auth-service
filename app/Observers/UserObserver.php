<?php

namespace App\Observers;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        UserCreated::dispatch($user);
    }

    public function deleted(User $user): void
    {
        UserDeleted::dispatch($user);
    }
}
