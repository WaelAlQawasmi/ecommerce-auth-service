<?php

namespace App\Providers;

use App\Passport\AccessToken;
use App\Passport\AccessTokenRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\AccessTokenRepository as PassportAccessTokenRepository;
use Laravel\Passport\Passport;

class PassportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Passport::useAccessTokenEntity(AccessToken::class);

        $this->app->singleton(
            PassportAccessTokenRepository::class,
            AccessTokenRepository::class,
        );
    }

    public function boot(): void
    {
        Passport::enablePasswordGrant();
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
    }
}
