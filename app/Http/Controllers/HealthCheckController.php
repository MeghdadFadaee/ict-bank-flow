<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function __invoke()
    {
        return response()->json(['status' => 'UP']);
    }
}
