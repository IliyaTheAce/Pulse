<?php

namespace App\Interfaces;


interface MonitorChecker
{
    /**
     * @param  array{
     *     url: string,
     *     method: string,
     *     timeout_ms: int,
     *     expected_status: int,
     *     headers: array<string, string>,
     *     assertions: list<array<string, mixed>>
     * }  $probe
     * @return array{
     *     duration_ms: int,
     *     http_status: int|null,
     *     status: string,
     *     error_type: string|null,
     *     assertion_failures: array<int, array<string, mixed>>|null,
     *     response_bytes: int|null
     * }
     */
    public function check(array $probe): array;
}
