<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;

abstract class ApiController extends Controller
{
    // All V1 controllers inherit authorization helpers from the base Controller
    // and return responses through the shared ApiResponse envelope.
}
