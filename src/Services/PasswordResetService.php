<?php

require_once __DIR__ . '/../Repositories/PessoaRepository.php';
require_once __DIR__ . '/../Repositories/PasswordResetRequestRepository.php';
require_once __DIR__ . '/../Repositories/PasswordResetTokenRepository.php';
require_once __DIR__ . '/MailService.php';

class PasswordResetService
{
    private const COOLDOWN_MINUTES = 15;
    private const MAX_REQUESTS_PER_24_HOURS = 3;

    private PessoaRepository $pessoaRepository;
    private PasswordResetRequestRepository $requestRepository;
    private PasswordResetTokenRepository $tokenRepository;
    private MailService $mailService;

    public function __construct()
    {
        $this->pessoaRepository = new PessoaRepository();
        $this->requestRepository = new PasswordResetRequestRepository();
        $this->tokenRepository = new PasswordResetTokenRepository();
        $this->mailService = new MailService();
    }

    public function solicitarResetPorEmail(string $email): void
    {
        $emailNormalizado = $this->normalizarEmail($email);
        $ipAddress = $this->obterIpSolicitacao();

        $pessoa = $this->pessoaRepository->buscarPorEmailAtivo($emailNormalizado);

        if (!$pessoa) {
            $this->requestRepository->registrar(null, $emailNormalizado, $ipAddress, 'unknown_email');
            return;
        }

        $pessoaId = (int) $pessoa['id'];

        if ($this->estaEmCooldown($pessoaId)) {
            $this->requestRepository->registrar($pessoaId, $emailNormalizado, $ipAddress, 'cooldown');
            return;
        }

        if ($this->atingiuLimite24Horas($pessoaId)) {
            $this->requestRepository->registrar($pessoaId, $emailNormalizado, $ipAddress, 'daily_limit');
            return;
        }

        $registroToken = $this->tokenRepository->buscarTokenValidoDaPessoa($pessoaId);

        if ($registroToken) {
            $token = $registroToken['token'];
        } else {
            $this->tokenRepository->invalidarTokensAtivosDaPessoa($pessoaId);

            $token = bin2hex(random_bytes(32));
            $expiraEm = date('Y-m-d H:i:s', time() + 3600);

            $this->tokenRepository->criarToken($pessoaId, $token, $expiraEm);
        }

        $resetLink = $this->montarLinkReset($token);

        try {
            $this->mailService->enviarEmail(
                $emailNormalizado,
                'Redefinicao de senha: Abba Fazenda (Sistema JTRO)',
                $this->montarHtmlReset($pessoa['nome'], $resetLink)
            );

            $this->requestRepository->registrar($pessoaId, $emailNormalizado, $ipAddress, 'sent');
        } catch (Throwable $exception) {
            $this->requestRepository->registrar($pessoaId, $emailNormalizado, $ipAddress, 'send_error');
            throw $exception;
        }
    }

    public function redefinirSenha(string $token, string $novaSenha): bool
    {
        $registroToken = $this->tokenRepository->buscarTokenValido($token);

        if (!$registroToken) {
            return false;
        }

        $this->pessoaRepository->atualizarSenhaEObrigacao((int) $registroToken['pessoa_id'], $novaSenha, false);
        $this->tokenRepository->marcarComoUsado((int) $registroToken['id']);

        return true;
    }

    public function tokenValido(string $token): bool
    {
        return $this->tokenRepository->buscarTokenValido($token) !== null;
    }

    private function montarLinkReset(string $token): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

        return $scheme . '://' . $host . '/redefinir_senha.php?token=' . urlencode($token);
    }

    private function estaEmCooldown(int $pessoaId): bool
    {
        $desde = date('Y-m-d H:i:s', time() - (self::COOLDOWN_MINUTES * 60));

        return $this->requestRepository->houveEnvioPorPessoaDesde($pessoaId, $desde);
    }

    private function atingiuLimite24Horas(int $pessoaId): bool
    {
        $desde = date('Y-m-d H:i:s', time() - 86400);

        return $this->requestRepository->contarEnviosPorPessoaDesde($pessoaId, $desde) >= self::MAX_REQUESTS_PER_24_HOURS;
    }

    private function normalizarEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function obterIpSolicitacao(): ?string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

        return $ip !== '' ? $ip : null;
    }

    private function registrarLinkParaTeste(string $email, string $link): void
    {
        $caminho = __DIR__ . '/../../storage/reset_links.log';
        $linha = sprintf(
            "[%s] %s => %s%s",
            date('Y-m-d H:i:s'),
            $email,
            $link,
            PHP_EOL
        );

        file_put_contents($caminho, $linha, FILE_APPEND);
    }

    private function montarHtmlReset(string $nome, string $resetLink): string
    {
        $nomeSeguro = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $linkSeguro = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

        return "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #222;'>
            <h2>Redefinicao de senha - Abba Fazenda (Sistema JTRO)</h2>
            <p>Ola, {$nomeSeguro}.</p>
            <p>Recebemos uma solicitacao para redefinir sua senha.</p>
            <p>
                <a href='{$linkSeguro}' style='display:inline-block;padding:10px 16px;background:#1565c0;color:#fff;text-decoration:none;border-radius:6px;'>
                    Redefinir senha
                </a>
            </p>
            <p>Esse link expira em 1 hora.</p>
            <p>Se voce nao solicitou essa alteracao, ignore este e-mail.</p>
        </div>
    ";
    }
}
