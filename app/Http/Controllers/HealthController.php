<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Service is healthy',
            'timestamp' => now()->toISOString()
        ]);
    }
}