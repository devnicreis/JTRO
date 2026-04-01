<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/RequestContext.php';
require_once __DIR__ . '/../src/Services/PasswordResetService.php';
require_once __DIR__ . '/../src/Services/TurnstileService.php';

Auth::start();

$service = new PasswordResetService();
$turnstile = new TurnstileService();

$mensagem = '';
$erro = '';
$turnstileEnabled = $turnstile->isEnabled();
$turnstileSiteKey = $turnstile->getSiteKey();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrf();

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $erro = 'Informe seu e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail valido.';
    } elseif ($turnstileEnabled) {
        try {
            $validation = $turnstile->validateSubmission(
                $_POST['cf-turnstile-response'] ?? null,
                RequestContext::clientIp()
            );

            if (!$validation['success']) {
                $erro = 'Confirme a verificacao de seguranca para continuar.';
            }
        } catch (Throwable $exception) {
            error_log('[JTRO] Falha ao validar Turnstile no esqueci minha senha: ' . $exception->getMessage());
            $erro = 'Nao foi possivel validar a verificacao de seguranca agora. Tente novamente.';
        }
    }

    if ($erro === '') {
        try {
            $service->solicitarResetPorEmail($email);
            $mensagem = 'Se houver uma conta com esse e-mail, enviaremos instrucoes para redefinicao de senha.';
        } catch (Throwable $exception) {
            error_log('[JTRO] Falha ao processar reset de senha: ' . $exception->getMessage());
            $erro = 'Nao foi possivel processar sua solicitacao agora. Tente novamente em alguns minutos.';
        }
    }
}

$pageTitle = 'Esqueci minha senha - JTRO';

require_once __DIR__ . '/../src/Views/auth/esqueci_senha.php';
