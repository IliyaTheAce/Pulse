<?php

namespace App\Services\Monitoring;

use App\Interfaces\MonitorChecker;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class HttpMonitorChecker implements MonitorChecker
{
    private const int MAX_RESPONSE_BYTES = 1_048_576;

    public function __construct(private readonly ProbeUrlGuard $guard)
    {
    }

    /**
     * @param array{
     *     url: string,
     *     method: string,
     *     timeout_ms: int,
     *     expected_status: int,
     *     headers: array<string, string>,
     *     assertions: list<array<string, mixed>>
     * } $probe
     **/
    public function check(array $probe): array
    {
        $startedAt = microtime(true);

        try {
            $this->guard->assertSafe($probe['url']);

            $timeoutSeconds = max(1, (int)ceil($probe['timeout_ms'] / 1000));
            $maxBytes = self::MAX_RESPONSE_BYTES;

            $response = Http::timeout($timeoutSeconds)
                ->connectTimeout($timeoutSeconds)
                ->withHeaders($probe['headers'])
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 3,
                        'on_redirect' => function ($request, $response, $uri) {
                            $this->guard->assertSafe((string)$uri);
                        },
                    ],
                    'on_headers' => function ($response) use ($maxBytes) {
                        $length = (int)$response->getHeaderLine('Content-Length');
                        if ($length > $maxBytes) {
                            throw new RuntimeException('response too large');
                        }
                    },
                    'progress' => function ($downloadTotal, $downloaded) use ($maxBytes) {
                        if ($downloaded > $maxBytes) {
                            throw new RuntimeException('response too large');
                        }
                    },
                ])
                ->send(strtoupper($probe["method"]), $probe['url']);

            $durationMs = (int)((microtime(true) - $startedAt) * 1000);
            $failures = $this->evaluateAssertions($probe, $response, $durationMs);
            $ok = $failures === [];

            return [
                'duration_ms' => $durationMs,
                'http_status' => $response->status(),
                'status' => $ok ? 'success' : 'failure',
                'error_type' => $ok ? null : 'assertion',
                'assertion_failures' => $ok ? null : $failures,
                'response_bytes' => strlen($response->body()),
            ];
        } catch (Throwable $e) {
            Log::warning($e->getMessage(), ['url' => $probe['url'] ?? null]);

            return [
                'duration_ms' => (int)((microtime(true) - $startedAt) * 1000),
                'http_status' => null,
                'status' => 'error',
                'error_type' => $this->classify($e),
                'assertion_failures' => null,
                'response_bytes' => null,
            ];
        }

    }

    /**
     * @param array<string, mixed> $probe
     * @return list<array<string, mixed>>
     */
    private function evaluateAssertions(array $probe, Response $response, int $durationMs): array
    {
        $failures = [];
        if ($response->status() !== (int)$probe['expected_status']) {
            $failures[] = [
                'type' => 'status',
                'field' => null,
                'operator' => 'equal',
                'expected_value' => $probe['expected_status'],
                'actual' => $response->status(),
            ];
        }
        foreach ($probe['assertions'] ?? [] as $assertion) {
            $failure = $this->evaluateAssertion($assertion, $response, $durationMs);
            if ($failure !== null) {
                $failures[] = $failure;
            }
        }
        return $failures;
    }

    /**
     * @param array<string, mixed> $assertion
     * @return array<string, mixed>|null
     */
    private function evaluateAssertion(array $assertion, Response $response, int $durationMs): ?array
    {
        $actual = match ($assertion['type']) {
            'status' => $response->status(),
            'json' => data_get(json_decode($response->body(), true), $assertion['field']),
            'contains' => $response->body(),
            'latency' => $durationMs,
            default => null,
        };
        $passed = match ($assertion['type']) {
            'contains' => str_contains((string)$actual, (string)$assertion['expected_value']),
            default => $this->compare(
                (string)$assertion['operator'],
                $assertion['expected_value'],
                $actual
            ),
        };
        if ($passed) {
            return null;
        }
        return [
            'type' => $assertion['type'],
            'field' => $assertion['field'] ?? null,
            'operator' => $assertion['operator'] ?? null,
            'expected_value' => $assertion['expected_value'],
            'actual' => $actual,
        ];
    }

    private function compare(string $operator, mixed $expected, mixed $actual): bool
    {
        return match ($operator) {
            'equal' => (string)$expected === (string)$actual,
            'not_equal' => (string)$expected !== (string)$actual,
            'contains' => str_contains((string)$actual, (string)$expected),
            'lt' => (float)$actual < (float)$expected,
            'gt' => (float)$actual > (float)$expected,
            default => false,
        };
    }

    private function classify(Throwable $e): string
    {
        $message = strtolower($e->getMessage());
        if (str_contains($message, 'blocked') || str_contains($message, 'invalid scheme') || str_contains($message, 'invalid url')) {
            return 'connection';
        }
        if (str_contains($message, 'too large')) {
            return 'http';
        }
        if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return 'timeout';
        }
        if (
            str_contains($message, 'could not resolve')
            || str_contains($message, 'name or service not known')
            || str_contains($message, 'nodename nor servname')
        ) {
            return 'dns';
        }
        if (str_contains($message, 'ssl') || str_contains($message, 'certificate') || str_contains($message, 'tls')) {
            return 'tls';
        }
        if ($e instanceof ConnectionException) {
            return 'connection';
        }
        return 'http';
    }
}
