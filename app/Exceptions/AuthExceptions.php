<?php

namespace App\Exceptions;

use App\Support\ApiError;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Domain-level failures of the Identity module. Each instance carries the stable
 * machine `code` and HTTP status defined by the auth spec, and renders itself
 * through the unified error envelope.
 *
 * These are all expected client-facing 4xx errors, so the exception implements
 * {@see ShouldntReport} to keep them out of the logs and Sentry.
 */
class AuthExceptions extends RuntimeException implements ShouldntReport
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $displayMessage,
    ) {
        parent::__construct($displayMessage);
    }

    public function render(Request $request): JsonResponse
    {
        return ApiError::make($this->getMessage(), $this->errorCode, $this->status);
    }

    public static function emailAlreadyExists(): self
    {
        return new self('EMAIL_ALREADY_EXISTS', Response::HTTP_UNPROCESSABLE_ENTITY, __('auth_email_unique'));
    }
    public static function invalidCredentials(): self
    {
        return new self('INVALID_CREDENTIALS', Response::HTTP_UNAUTHORIZED, __('auth_invalid_credentials'));
    }
}
