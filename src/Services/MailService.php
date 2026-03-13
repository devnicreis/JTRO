<?php

class MailService
{
    private string $apiKey;
    private string $from;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/local.php';

        $this->apiKey = $config['resend_api_key'] ?? '';
        $this->from = $config['mail_from'] ?? 'onboarding@resend.dev';
    }

    public function enviarEmail(string $to, string $subject, string $html): bool
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('API key do Resend não configurada.');
        }

        $payload = json_encode([
            'from' => $this->from,
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
        ]);

        if (!function_exists('curl_init')) {
            throw new RuntimeException('Extensão cURL do PHP não está habilitada.');
        }

        $ch = curl_init('https://api.resend.com/emails');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $erro = curl_error($ch);
            throw new RuntimeException('Erro ao enviar e-mail: ' . $erro);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Resend retornou erro: ' . $response);
        }

        return true;
    }
}