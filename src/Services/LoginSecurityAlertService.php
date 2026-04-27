<?php

require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/../Core/AppConfig.php';

class LoginSecurityAlertService
{
    private MailService $mailService;
    private string $supportContact;

    public function __construct()
    {
        $this->mailService = new MailService();
        $this->supportContact = AppConfig::getString(
            'privacy_support_contact',
            'a administracao responsavel pelo seu cadastro ou o suporte do JTRO'
        );
    }

    public function enviarAlertaBloqueio(array $pessoa, ?string $ipAddress): void
    {
        $email = trim((string) ($pessoa['email'] ?? ''));
        if ($email === '') {
            return;
        }

        $nome = trim((string) ($pessoa['nome'] ?? ''));
        $ipTexto = $ipAddress !== null && $ipAddress !== '' ? $ipAddress : 'nao identificado';
        $horario = date('d/m/Y H:i');
        $subject = 'Alerta de seguranca no acesso ao JTRO';

        $html = sprintf(
            '<p>Olá, %s.</p>'
            . '<p>Detectamos varias tentativas de acesso ao JTRO com o seu CPF em %s.</p>'
            . '<p>Por segurança, o login foi temporariamente bloqueado por 5 minutos.</p>'
            . '<p>IP de origem: %s</p>'
            . '<p>Se não foi você, utilize apenas o fluxo normal de "Esqueci minha senha" ou entre em contato com %s.</p>',
            $this->escapeHtml($nome !== '' ? $nome : 'usuario'),
            $this->escapeHtml($horario),
            $this->escapeHtml($ipTexto),
            $this->escapeHtml($this->supportContact)
        );

        $this->mailService->enviarEmail($email, $subject, $html);
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
