<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Services/PasswordResetService.php';

Auth::start();

$service = new PasswordResetService();

$mensagem = '';
$erro = '';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '' || !$service->tokenValido($token)) {
    $erro = 'Token invalido ou expirado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    Auth::requireCsrf();

    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if ($novaSenha !== $confirmarSenha) {
        $erro = 'A confirmacao da nova senha nao confere.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $novaSenha)) {
        $erro = 'A nova senha deve ter pelo menos 8 caracteres, com letra minuscula, maiuscula, numero e simbolo.';
    } else {
        $ok = $service->redefinirSenha($token, $novaSenha);

        if ($ok) {
            header('Location: /login.php?senha_redefinida=1');
            exit;
        }

        $erro = 'Token invalido ou expirado.';
    }
}

$pageTitle = 'Redefinir senha - JTRO';

require_once __DIR__ . '/../src/Views/auth/redefinir_senha.php';
