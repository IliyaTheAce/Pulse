<?php

namespace App\Services\Monitoring;

use RuntimeException;

class ProbeUrlGuard
{
    public function assertSafe(string $uri): void
    {
        $parts = parse_url($uri);

        if (! isset($parts['scheme'], $parts['host'])) {
            throw new RuntimeException('invalid url');
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('invalid scheme');
        }

        $host = strtolower($parts['host']);

        if (
            $host === 'localhost'
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal')
        ) {
            throw new RuntimeException('blocked host');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertPublicIp($host);

            return;
        }

        $ips = [];
        foreach (dns_get_record($host, DNS_A + DNS_AAAA) ?: [] as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            }
            if (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        if ($ips === []) {
            $ips = gethostbynamel($host) ?: [];
        }

        if ($ips === []) {
            throw new RuntimeException('could not resolve host');
        }

        foreach ($ips as $ip) {
            $this->assertPublicIp($ip);
        }
    }

    private function assertPublicIp(string $ip): void
    {
        if (in_array($ip, ['169.254.169.254', 'fd00:ec2::254'], true)) {
            throw new RuntimeException('blocked ip');
        }

        $valid = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($valid === false) {
            throw new RuntimeException('blocked ip');
        }
    }
}
