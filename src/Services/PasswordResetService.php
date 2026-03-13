<?php

require_once __DIR__ . '/../Repositories/PessoaRepository.php';
require_once __DIR__ . '/../Repositories/PasswordResetTokenRepository.php';
require_once __DIR__ . '/MailService.php';

class PasswordResetService
{
    private PessoaRepository $pessoaRepository;
    private PasswordResetTokenRepository $tokenRepository;
    private MailService $mailService;

    public function __construct()
    {
        $this->pessoaRepository = new PessoaRepository();
        $this->tokenRepository = new PasswordResetTokenRepository();
        $this->mailService = new MailService();
    }

    public function solicitarResetPorEmail(string $email): void
    {
        $pessoa = $this->pessoaRepository->buscarPorEmailAtivo($email);

        if (!$pessoa) {
            return;
        }

        $this->tokenRepository->invalidarTokensAtivosDaPessoa((int) $pessoa['id']);

        $token = bin2hex(random_bytes(32));
        $expiraEm = date('Y-m-d H:i:s', time() + 3600);

        $this->tokenRepository->criarToken((int) $pessoa['id'], $token, $expiraEm);

        $resetLink = $this->montarLinkReset($token);

        $this->mailService->enviarEmail(
            $email,
            'Redefinição de senha: Abba Fazenda (Sistema JTRO)',
            $this->montarHtmlReset($pessoa['nome'], $resetLink)
        );
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
            <h2>Redefinição de senha - Abba Fazenda (Sistema JTRO)</h2>
            <p>Olá, {$nomeSeguro}.</p>
            <p>Recebemos uma solicitação para redefinir sua senha.</p>
            <p>
                <a href='{$linkSeguro}' style='display:inline-block;padding:10px 16px;background:#1565c0;color:#fff;text-decoration:none;border-radius:6px;'>
                    Redefinir senha
                </a>
            </p>
            <p>Esse link expira em 1 hora.</p>
            <p>Se você não solicitou essa alteração, ignore este e-mail.</p>
        </div>
    ";
    }
}
