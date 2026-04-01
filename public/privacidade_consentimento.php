<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/PrivacySettings.php';
require_once __DIR__ . '/../src/Core/RequestContext.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/PrivacyConsentRepository.php';

Auth::requireLogin();

$repo = new PessoaRepository();
$consentRepo = new PrivacyConsentRepository();
$usuarioId = Auth::id();
$erro = '';
$pageTitle = 'Privacidade e LGPD - JTRO';

$pessoa = $repo->buscarPorId((int) $usuarioId);
if (!$pessoa) {
    Auth::logout();
    header('Location: /login.php');
    exit;
}

if (PrivacySettings::consentimentoAtual($pessoa)) {
    Auth::atualizarSessao($pessoa);
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aceitoPrivacidade = isset($_POST['aceito_privacidade']) && $_POST['aceito_privacidade'] === '1';

    if (!$aceitoPrivacidade) {
        $erro = 'Para continuar, aceite os Termos de Uso e a Politica de Privacidade.';
    } else {
        $ipAddress = RequestContext::clientIp();
        $userAgent = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        $consentRepo->registrarAceite(
            (int) $usuarioId,
            $ipAddress,
            $userAgent !== '' ? mb_substr($userAgent, 0, 500) : null
        );

        $pessoaAtualizada = $repo->buscarPorId((int) $usuarioId);
        if ($pessoaAtualizada !== null) {
            Auth::atualizarSessao($pessoaAtualizada);
        }

        header('Location: /index.php?privacidade_aceita=1');
        exit;
    }
}

$nomeUsuario = $pessoa['nome'] ?? 'Usuario';
$termosVersao = PrivacySettings::termosVersao();
$politicaVersao = PrivacySettings::politicaVersao();

require_once __DIR__ . '/../src/Views/auth/privacidade_consentimento.php';
