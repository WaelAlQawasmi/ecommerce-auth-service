<?php

use App\Providers\AppServiceProvider;
use App\Providers\PassportServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    PassportServiceProvider::class,
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
];
