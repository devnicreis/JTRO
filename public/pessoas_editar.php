<?php

require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

$repo = new PessoaRepository();
$auditoria = new AuditoriaService();

$mensagem = '';
$erro = '';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    die('Pessoa inválida.');
}

$pessoa = $repo->buscarPorId($id);

if (!$pessoa) {
    die('Pessoa não encontrada.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cargo = $_POST['cargo'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    $nome = preg_replace('/\s+/', ' ', $nome);

    if ($nome === '' || $cpf === '' || $cargo === '') {
        $erro = 'Preencha nome, CPF e perfil do sistema.';
    } elseif (!preg_match('/^[\p{L}\s]+$/u', $nome)) {
        $erro = 'O nome deve conter apenas letras e espaços.';
    } elseif (!ctype_digit($cpf)) {
        $erro = 'O CPF deve conter somente números, sem pontos e traços.';
    } elseif (strlen($cpf) !== 11) {
        $erro = 'O CPF deve conter exatamente 11 dígitos.';
    } elseif ($repo->buscarPorCpfExcetoId($cpf, $id) !== null) {
        $erro = 'Já existe outra pessoa cadastrada com esse CPF.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif ($email !== '' && $repo->buscarPorEmailExcetoId($email, $id) !== null) {
        $erro = 'Já existe outra pessoa cadastrada com esse e-mail.';
    } else {
        if ($novaSenha !== '' || $confirmarSenha !== '') {
            if ($novaSenha !== $confirmarSenha) {
                $erro = 'A confirmação da senha não confere.';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $novaSenha)) {
                $erro = 'A senha deve ter pelo menos 8 caracteres, com letra minúscula, maiúscula, número e símbolo.';
            }
        }

        if ($erro === '') {
            try {
                $repo->atualizar($id, $nome, $cpf, $email, $cargo);

                $auditoria->registrar(
                    'atualizar',
                    'pessoa',
                    $id,
                    "Pessoa atualizada: {$nome}.",
                    null,
                    null,
                    null
                );

                if ($novaSenha !== '') {
                    $repo->atualizarSenhaEObrigacao($id, $novaSenha, true);

                    $auditoria->registrar(
                        'redefinir',
                        'senha',
                        $id,
                        "Senha redefinida por administrador para {$nome}.",
                        null,
                        null,
                        null
                    );
                }

                $mensagem = 'Pessoa atualizada com sucesso.';
                $pessoa = $repo->buscarPorId($id);
            } catch (Exception $e) {
                $erro = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Editar Pessoa - JTRO';

require_once __DIR__ . '/../src/Views/pessoas/editar.php';
