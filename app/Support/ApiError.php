<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Single source of truth for the auth module's error envelope:
 *   { "success": false, "message": ..., "code": ..., "errors": ... }
 */
final class ApiError
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function make(string $message, string $code, int $status, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
            'errors' => (object) $errors,
        ], $status);
    }
}
