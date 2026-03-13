<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';

Auth::requireLogin();

$pageTitle = 'Meu Perfil - JTRO';
$repo = new PessoaRepository();
$usuarioSessao = Auth::usuario();
$usuarioId = Auth::id();

$mensagem = '';
$erro = '';

$pessoa = $repo->buscarPorId($usuarioId);

if (!$pessoa) {
    Auth::logout();
    header('Location: /login.php');
    exit;
}

$forcarTroca = Auth::precisaTrocarSenha() || (isset($_GET['forcar_troca']) && $_GET['forcar_troca'] === '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'atualizar_email') {
        $email = trim($_POST['email'] ?? '');

        if ($email === '') {
            $erro = 'Informe um e-mail.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um e-mail válido.';
        } elseif ($repo->buscarPorEmailExcetoId($email, $usuarioId) !== null) {
            $erro = 'Esse e-mail já está sendo usado por outra pessoa.';
        } else {
            try {
                $repo->atualizar($pessoa['id'], $pessoa['nome'], $pessoa['cpf'], $email, $pessoa['cargo']);
                $pessoa = $repo->buscarPorId($usuarioId);
                Auth::atualizarSessao($pessoa);
                $mensagem = 'E-mail atualizado com sucesso.';
            } catch (Exception $e) {
                $erro = $e->getMessage();
            }
        }
    }

    if ($acao === 'alterar_senha') {
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        if (!$forcarTroca && !password_verify($senhaAtual, $pessoa['senha_hash'] ?? '')) {
            $erro = 'A senha atual está incorreta.';
        } elseif ($novaSenha !== $confirmarSenha) {
            $erro = 'A confirmação da nova senha não confere.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $novaSenha)) {
            $erro = 'A nova senha deve ter pelo menos 8 caracteres, com letra minúscula, maiúscula, número e símbolo.';
        } else {
            $repo->atualizarSenhaEObrigacao($usuarioId, $novaSenha, false);
            $pessoa = $repo->buscarPorId($usuarioId);
            Auth::atualizarSessao($pessoa);

            if ($forcarTroca) {
                header('Location: /index.php?senha_alterada=1');
                exit;
            }

            $mensagem = 'Senha alterada com sucesso.';
        }
    }
}

require_once __DIR__ . '/../src/Views/pessoas/meu_perfil.php';