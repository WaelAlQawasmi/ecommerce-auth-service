<?php

namespace App\Providers;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserRegistered;
use App\Listeners\FlushUserCountCache;
use App\Listeners\SendWelcomeEmail;
use App\Models\User;
use App\Observers\UserObserver;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        Event::listen(UserRegistered::class, SendWelcomeEmail::class);
        Event::listen(UserCreated::class, FlushUserCountCache::class);
        Event::listen(UserDeleted::class, FlushUserCountCache::class);

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->info->title = config('app.name').' API';
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')->as('passport')
                );
            });
    }
}
