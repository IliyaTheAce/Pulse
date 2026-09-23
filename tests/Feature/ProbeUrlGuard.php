<?php

use App\Services\Monitoring\ProbeUrlGuard;

it('blocks loopback', function () {
    expect(fn () => (new ProbeUrlGuard)->assertSafe('http://127.0.0.1/'))
        ->toThrow(RuntimeException::class);
});
it('blocks cloud metadata', function () {
    expect(fn () => (new ProbeUrlGuard)->assertSafe('http://169.254.169.254/'))
        ->toThrow(RuntimeException::class);
});