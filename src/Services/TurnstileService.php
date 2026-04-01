<?php

require_once __DIR__ . '/../Core/AppConfig.php';

class TurnstileService
{
    private string $siteKey;
    private string $secretKey;
    private array $allowedHostnames;

    public function __construct()
    {
        $this->siteKey = AppConfig::getString('turnstile_site_key', '');
        $this->secretKey = AppConfig::getString('turnstile_secret_key', '');
        $this->allowedHostnames = $this->normalizarHostnamesPermitidos(
            AppConfig::getArray('turnstile_allowed_hostnames', [])
        );
    }

    public function isEnabled(): bool
    {
        return $this->siteKey !== '' && $this->secretKey !== '';
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function validateSubmission(?string $token, ?string $remoteIp = null): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => true,
                'error_codes' => [],
            ];
        }

        $token = trim((string) $token);
        if ($token === '') {
            return [
                'success' => false,
                'error_codes' => ['missing-input-response'],
            ];
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('Extensao cURL do PHP nao esta habilitada.');
        }

        $payload = http_build_query([
            'secret' => $this->secretKey,
            'response' => $token,
            'remoteip' => $remoteIp !== null && trim($remoteIp) !== '' ? trim($remoteIp) : null,
        ]);

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            throw new RuntimeException('Nao foi possivel validar o Turnstile: ' . $error);
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Nao foi possivel validar o Turnstile no momento.');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Resposta invalida ao validar o Turnstile.');
        }

        $hostname = strtolower(trim((string) ($decoded['hostname'] ?? '')));
        $success = !empty($decoded['success']);

        if ($success && !$this->hostnamePermitido($hostname)) {
            $success = false;
            $decoded['error-codes'][] = 'hostname-mismatch';
        }

        return [
            'success' => $success,
            'error_codes' => array_values((array) ($decoded['error-codes'] ?? [])),
            'hostname' => $hostname,
        ];
    }

    private function hostnamePermitido(string $hostname): bool
    {
        if ($hostname === '') {
            return false;
        }

        if ($this->allowedHostnames === []) {
            return true;
        }

        return in_array($hostname, $this->allowedHostnames, true);
    }

    private function normalizarHostnamesPermitidos(array $hostnames): array
    {
        if ($hostnames === []) {
            $baseUrl = AppConfig::getString('app_base_url', '');
            $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
            if ($host !== '') {
                $hostnames[] = $host;
            }
        }

        $normalized = [];
        foreach ($hostnames as $hostname) {
            $hostname = strtolower(trim((string) $hostname));
            if ($hostname === '') {
                continue;
            }

            $normalized[$hostname] = true;
        }

        return array_keys($normalized);
    }
}
