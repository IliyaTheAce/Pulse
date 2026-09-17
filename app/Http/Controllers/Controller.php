<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\ResponseTrait;

abstract class Controller
{
    use ApiResponse,ResponseTrait;
}
