<?php

require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';

require_once __DIR__ . '/../src/Core/Auth.php';
Auth::requireAdmin();

$repo = new PessoaRepository();

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
    } elseif ($repo->buscarPorCpfExcetoId($cpf, $id) !== null) {
        $erro = 'Já existe outra pessoa cadastrada com esse CPF.';
    } else {
        try {
            $repo->atualizar($id, $nome, $cpf, $cargo);
            $mensagem = 'Pessoa atualizada com sucesso.';
            $pessoa = $repo->buscarPorId($id);
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

$pageTitle = 'Editar Pessoa - JTRO';

require_once __DIR__ . '/../src/Views/pessoas/editar.php';