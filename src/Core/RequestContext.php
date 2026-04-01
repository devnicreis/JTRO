<?php

require_once __DIR__ . '/AppConfig.php';

class RequestContext
{
    public static function clientIp(): ?string
    {
        $candidates = [];

        if (self::trustProxyHeaders()) {
            $cfConnectingIp = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
            if ($cfConnectingIp !== '') {
                $candidates[] = $cfConnectingIp;
            }

            $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
            if ($forwardedFor !== '') {
                foreach (explode(',', $forwardedFor) as $item) {
                    $item = trim($item);
                    if ($item !== '') {
                        $candidates[] = $item;
                    }
                }
            }
        }

        $remoteAddress = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remoteAddress !== '') {
            $candidates[] = $remoteAddress;
        }

        foreach ($candidates as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function isHttps(): bool
    {
        $https = strtolower(trim((string) ($_SERVER['HTTPS'] ?? '')));
        if ($https !== '' && $https !== 'off') {
            return true;
        }

        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        if (!self::trustProxyHeaders()) {
            return false;
        }

        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        if ($forwardedProto === 'https') {
            return true;
        }

        $cfVisitor = trim((string) ($_SERVER['HTTP_CF_VISITOR'] ?? ''));
        if ($cfVisitor === '') {
            return false;
        }

        $decoded = json_decode($cfVisitor, true);
        return is_array($decoded) && strtolower((string) ($decoded['scheme'] ?? '')) === 'https';
    }

    public static function appOrigin(): ?array
    {
        return self::parseUrlOrigin(AppConfig::getString('app_base_url', ''));
    }

    public static function currentOrigin(): ?array
    {
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            $host = trim((string) ($_SERVER['SERVER_NAME'] ?? ''));
        }

        if ($host === '') {
            return null;
        }

        $scheme = self::isHttps() ? 'https' : 'http';
        return self::parseUrlOrigin($scheme . '://' . $host);
    }

    public static function parseUrlOrigin(?string $url): ?array
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($scheme === '' || $host === '') {
            return null;
        }

        $port = parse_url($url, PHP_URL_PORT);

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port !== null ? (int) $port : self::defaultPort($scheme),
        ];
    }

    public static function sameOrigin(array $left, array $right): bool
    {
        return ($left['scheme'] ?? null) === ($right['scheme'] ?? null)
            && ($left['host'] ?? null) === ($right['host'] ?? null)
            && (int) ($left['port'] ?? 0) === (int) ($right['port'] ?? 0);
    }

    private static function trustProxyHeaders(): bool
    {
        return AppConfig::getBool('trust_proxy_headers', false);
    }

    private static function defaultPort(string $scheme): int
    {
        return $scheme === 'https' ? 443 : 80;
    }
}
