<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    protected function successResponse(?string $message = null, $data = null, int $code = Response::HTTP_OK): JsonResponse
    {
        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
            ], $code);
    }
}
