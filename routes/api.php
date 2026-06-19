<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Versioned route files live under routes/api/{version}.php.
| The global /api prefix is applied by bootstrap/app.php.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->group(base_path('routes/api/v1.php'));
