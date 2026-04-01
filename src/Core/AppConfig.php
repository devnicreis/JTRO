<?php

class AppConfig
{
    private const ENV_PREFIX = 'JTRO_';
    private const SUPPORTED_KEYS = [
        'app_base_url' => 'string',
        'resend_api_key' => 'string',
        'mail_from' => 'string',
        'turnstile_site_key' => 'string',
        'turnstile_secret_key' => 'string',
        'turnstile_allowed_hostnames' => 'array',
        'privacy_terms_version' => 'string',
        'privacy_policy_version' => 'string',
        'privacy_support_contact' => 'string',
        'trust_proxy_headers' => 'bool',
        'enable_csp' => 'bool',
        'csp_report_only' => 'bool',
        'enable_hsts' => 'bool',
        'hsts_max_age' => 'int',
    ];

    private static ?array $config = null;
    private static bool $dotenvLoaded = false;

    public static function all(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        self::loadDotEnv();

        $configPath = dirname(__DIR__, 2) . '/config/local.php';
        $config = file_exists($configPath) ? require $configPath : [];
        $config = is_array($config) ? $config : [];

        foreach (self::SUPPORTED_KEYS as $key => $type) {
            if (array_key_exists($key, $config)) {
                $config[$key] = self::normalizeValue($type, $config[$key]);
            }

            [$found, $envValue] = self::envValue(self::envName($key));
            if ($found) {
                $config[$key] = self::normalizeValue($type, $envValue);
            }
        }

        self::$config = $config;

        return self::$config;
    }

    public static function getString(string $key, string $default = ''): string
    {
        $config = self::all();
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        $value = trim((string) $config[$key]);
        return $value !== '' ? $value : $default;
    }

    public static function getArray(string $key, array $default = []): array
    {
        $config = self::all();
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        $value = self::normalizeArray($config[$key]);
        return $value !== [] ? $value : $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $config = self::all();
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        $value = self::normalizeBool($config[$key]);
        return $value ?? $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $config = self::all();
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        $value = $config[$key];
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    private static function loadDotEnv(): void
    {
        if (self::$dotenvLoaded) {
            return;
        }

        self::$dotenvLoaded = true;

        $envPath = dirname(__DIR__, 2) . '/.env';
        if (!is_file($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            $separator = strpos($line, '=');
            if ($separator === false) {
                continue;
            }

            $name = trim(substr($line, 0, $separator));
            if ($name === '') {
                continue;
            }

            if (self::envExists($name)) {
                continue;
            }

            $value = trim(substr($line, $separator + 1));
            $value = self::stripInlineComment($value);
            $value = self::unquote($value);

            self::setEnv($name, $value);
        }
    }

    private static function envName(string $key): string
    {
        return self::ENV_PREFIX . strtoupper($key);
    }

    private static function envExists(string $name): bool
    {
        return array_key_exists($name, $_ENV)
            || array_key_exists($name, $_SERVER)
            || getenv($name) !== false;
    }

    private static function envValue(string $name): array
    {
        if (array_key_exists($name, $_ENV)) {
            return [true, $_ENV[$name]];
        }

        if (array_key_exists($name, $_SERVER)) {
            return [true, $_SERVER[$name]];
        }

        $value = getenv($name);
        if ($value !== false) {
            return [true, $value];
        }

        return [false, null];
    }

    private static function setEnv(string $name, string $value): void
    {
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;

        if (function_exists('putenv')) {
            putenv($name . '=' . $value);
        }
    }

    private static function normalizeValue(string $type, mixed $value): mixed
    {
        return match ($type) {
            'string' => trim((string) $value),
            'array' => self::normalizeArray($value),
            'bool' => self::normalizeBool($value) ?? false,
            'int' => is_numeric($value) ? (int) $value : 0,
            default => $value,
        };
    }

    private static function normalizeArray(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }

            $normalized[$item] = true;
        }

        return array_keys($normalized);
    }

    private static function normalizeBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return false;
        }

        return match ($value) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }

    private static function stripInlineComment(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        if ($value[0] === '"' || $value[0] === "'") {
            return $value;
        }

        $commentPosition = strpos($value, ' #');
        if ($commentPosition === false) {
            return $value;
        }

        return rtrim(substr($value, 0, $commentPosition));
    }

    private static function unquote(string $value): string
    {
        $length = strlen($value);
        if ($length < 2) {
            return $value;
        }

        $first = $value[0];
        $last = $value[$length - 1];

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $value = substr($value, 1, -1);
        }

        return str_replace(
            ['\"', "\\'", '\n', '\r'],
            ['"', "'", PHP_EOL, "\r"],
            $value
        );
    }
}
