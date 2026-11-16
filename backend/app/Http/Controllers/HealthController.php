<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Reports whether the API and its database are reachable.
 * Used by Render's health check and by the frontend to show the backend is up.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            $databaseVersion = DB::scalar('SHOW server_version');
        } catch (Throwable $exception) {
            // The API cannot serve anything useful without its database, so report unhealthy (503)
            // and log the real error instead of returning its details to the client.
            report($exception);

            return response()->json([
                'status' => 'error',
                'database' => null,
                'checked_at' => now()->toIso8601String(),
            ], 503);
        }

        return response()->json([
            'status' => 'ok',
            'database' => "PostgreSQL {$databaseVersion}",
            'checked_at' => now()->toIso8601String(),
        ]);
    }
}
