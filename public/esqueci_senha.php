<?php

require_once __DIR__ . '/../src/Services/PasswordResetService.php';

$service = new PasswordResetService();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $erro = 'Informe seu e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        try {
            $service->solicitarResetPorEmail($email);
            $mensagem = 'Se houver uma conta com esse e-mail, enviaremos instruções para redefinição de senha.';
        } catch (Exception $e) {
            $erro = 'Erro ao enviar e-mail: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Esqueci minha senha - JTRO';

require_once __DIR__ . '/../src/Views/auth/esqueci_senha.php';