<?php

require_once __DIR__ . '/../src/Models/Pessoa.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

$repo = new PessoaRepository();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cargo = $_POST['cargo'] ?? '';

    $nome = preg_replace('/\s+/', ' ', $nome);

    if ($nome === '' || $cpf === '' || $cargo === '') {
        $erro = 'Preencha nome, CPF e perfil do sistema.';
    } elseif (!preg_match('/^[\p{L}\s]+$/u', $nome)) {
        $erro = 'O nome deve conter apenas letras e espaços.';
    } elseif (!ctype_digit($cpf)) {
        $erro = 'O CPF deve conter somente números, sem pontos e traços.';
    } elseif (strlen($cpf) !== 11) {
        $erro = 'O CPF deve conter exatamente 11 dígitos.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif ($email !== '' && $repo->buscarPorEmail($email) !== null) {
        $erro = 'Já existe uma pessoa cadastrada com esse e-mail.';
    } else {
        $pessoaExistente = $repo->buscarPorCpf($cpf);

        if ($pessoaExistente !== null) {
            if ((int)$pessoaExistente['ativo'] === 0) {
                $erro = 'Usuário desativado. Por favor, contate a administração.';
            } else {
                $erro = 'Já existe uma pessoa cadastrada com esse CPF.';
            }
        } else {
            try {
                $pessoa = new Pessoa($nome, $cpf, $cargo);
                $repo->salvar($pessoa, $email);
                $mensagem = 'Pessoa cadastrada com sucesso.';
            } catch (Exception $e) {
                $erro = $e->getMessage();
            }
        }
    }
}

$pessoas = $repo->listarTodos();
$pageTitle = 'Cadastro de Pessoas - JTRO';

require_once __DIR__ . '/../src/Views/pessoas/index.php';
