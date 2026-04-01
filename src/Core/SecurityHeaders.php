<?php

require_once __DIR__ . '/AppConfig.php';
require_once __DIR__ . '/RequestContext.php';

class SecurityHeaders
{
    public static function send(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Origin-Agent-Cluster: ?1');

        self::sendContentSecurityPolicy();

        if (AppConfig::getBool('enable_hsts', false) && RequestContext::isHttps()) {
            $maxAge = max(0, AppConfig::getInt('hsts_max_age', 31536000));
            header('Strict-Transport-Security: max-age=' . $maxAge);
        }
    }

    public static function sendNoStore(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Robots-Tag: noindex, nofollow, noarchive');
    }

    private static function sendContentSecurityPolicy(): void
    {
        if (!AppConfig::getBool('enable_csp', true)) {
            return;
        }

        $policy = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "script-src 'self' 'unsafe-inline' https://challenges.cloudflare.com https://cdn.quilljs.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.quilljs.com",
            "font-src 'self' data: https://fonts.gstatic.com",
            "img-src 'self' data: https:",
            "connect-src 'self' https://challenges.cloudflare.com https://viacep.com.br",
            "frame-src 'self' https://challenges.cloudflare.com",
        ]);

        $headerName = AppConfig::getBool('csp_report_only', false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        header($headerName . ': ' . $policy);
    }
}
