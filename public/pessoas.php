<?php

require_once __DIR__ . '/../src/Models/Pessoa.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

function normalizarNomePessoa(string $nome): string
{
    return preg_replace('/\s+/', ' ', trim($nome));
}

function validarDataNascimentoPessoa(string $data): bool
{
    if ($data === '') {
        return false;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt !== false && $dt->format('Y-m-d') === $data && $dt <= new DateTime('today');
}

function limparTelefonePessoa(string $telefone): string
{
    return preg_replace('/\D+/', '', $telefone);
}

function validarTelefoneFixoPessoa(string $telefone): bool
{
    return $telefone === '' || in_array(strlen($telefone), [10, 11], true);
}

function validarTelefoneMovelPessoa(string $telefone): bool
{
    return $telefone === '' || strlen($telefone) === 11;
}

$repo = new PessoaRepository();
$grupoRepo = new GrupoFamiliarRepository();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = normalizarNomePessoa($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cargo = $_POST['cargo'] ?? '';
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $estadoCivil = $_POST['estado_civil'] ?? '';
    $nomeConjuge = normalizarNomePessoa($_POST['nome_conjuge'] ?? '');
    $ehLider = isset($_POST['eh_lider']) ? 1 : 0;
    $liderGrupoFamiliar = isset($_POST['lider_grupo_familiar']) ? 1 : 0;
    $liderDepartamento = isset($_POST['lider_departamento']) ? 1 : 0;
    $grupoFamiliarId = (int) ($_POST['grupo_familiar_id'] ?? 0);
    $telefoneFixo = limparTelefonePessoa($_POST['telefone_fixo'] ?? '');
    $telefoneMovel = limparTelefonePessoa($_POST['telefone_movel'] ?? '');
    $concluiuIntegracao = (int) ($_POST['concluiu_integracao'] ?? -1);
    $participouRetiroIntegracao = (int) ($_POST['participou_retiro_integracao'] ?? -1);

    $estadosValidos = ['solteiro', 'casado', 'uniao_estavel', 'divorciado', 'viuvo'];

    if (
        $nome === '' || $cpf === '' || $cargo === '' || $dataNascimento === '' || $estadoCivil === ''
        || $concluiuIntegracao < 0 || $participouRetiroIntegracao < 0
    ) {
        $erro = 'Preencha todos os campos obrigatórios do cadastro.';
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
    } elseif (!validarDataNascimentoPessoa($dataNascimento)) {
        $erro = 'Informe uma data de nascimento válida.';
    } elseif (!in_array($estadoCivil, $estadosValidos, true)) {
        $erro = 'Selecione um estado civil válido.';
    } elseif (in_array($estadoCivil, ['casado', 'uniao_estavel'], true) && $nomeConjuge === '') {
        $erro = 'Informe o nome do parceiro para o estado civil selecionado.';
    } elseif ($nomeConjuge !== '' && !preg_match('/^[\p{L}\s]+$/u', $nomeConjuge)) {
        $erro = 'O nome do parceiro deve conter apenas letras e espaços.';
    } elseif (!validarTelefoneFixoPessoa($telefoneFixo)) {
        $erro = 'Informe um telefone fixo válido com DDD.';
    } elseif (!validarTelefoneMovelPessoa($telefoneMovel)) {
        $erro = 'Informe um telefone móvel válido com DDD.';
    } elseif ($ehLider === 1 && $liderGrupoFamiliar === 0 && $liderDepartamento === 0) {
        $erro = 'Marque ao menos uma função de liderança.';
    } else {
        $pessoaExistente = $repo->buscarPorCpf($cpf);

        if ($pessoaExistente !== null) {
            if ((int) $pessoaExistente['ativo'] === 0) {
                $erro = 'Usuário desativado. Por favor, contate a administração.';
            } else {
                $erro = 'Já existe uma pessoa cadastrada com esse CPF.';
            }
        } else {
            try {
                $pessoa = new Pessoa($nome, $cpf, $cargo);
                $repo->salvar($pessoa, [
                    'email' => $email,
                    'data_nascimento' => $dataNascimento,
                    'estado_civil' => $estadoCivil,
                    'nome_conjuge' => in_array($estadoCivil, ['casado', 'uniao_estavel'], true) ? $nomeConjuge : null,
                    'eh_lider' => $ehLider,
                    'lider_grupo_familiar' => $ehLider ? $liderGrupoFamiliar : 0,
                    'lider_departamento' => $ehLider ? $liderDepartamento : 0,
                    'grupo_familiar_id' => $grupoFamiliarId > 0 ? $grupoFamiliarId : null,
                    'telefone_fixo' => $telefoneFixo,
                    'telefone_movel' => $telefoneMovel,
                    'concluiu_integracao' => $concluiuIntegracao,
                    'integracao_conclusao_manual' => $concluiuIntegracao,
                    'participou_retiro_integracao' => $participouRetiroIntegracao,
                ]);
                $mensagem = 'Pessoa cadastrada com sucesso.';
                $_POST = [];
            } catch (Exception $e) {
                $erro = $e->getMessage();
            }
        }
    }
}

$filtros = [
    'id' => trim($_GET['id'] ?? ''),
    'nome' => trim($_GET['nome'] ?? ''),
    'cpf' => trim($_GET['cpf'] ?? ''),
    'email' => trim($_GET['email'] ?? ''),
    'telefone' => trim($_GET['telefone'] ?? ''),
    'contato' => trim($_GET['contato'] ?? ''),
    'cargo' => trim($_GET['cargo'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'data_nascimento' => trim($_GET['data_nascimento'] ?? ''),
    'estado_civil' => trim($_GET['estado_civil'] ?? ''),
    'eh_lider' => trim($_GET['eh_lider'] ?? ''),
    'lideranca' => trim($_GET['lideranca'] ?? ''),
    'grupo_familiar_id' => trim($_GET['grupo_familiar_id'] ?? ''),
    'concluiu_integracao' => trim($_GET['concluiu_integracao'] ?? ''),
    'participou_retiro_integracao' => trim($_GET['participou_retiro_integracao'] ?? ''),
];

$pessoas = $repo->listarTodos($filtros);
$gruposFamiliares = $grupoRepo->listarAtivos();
$pageTitle = 'Cadastro de Pessoas - JTRO';

require_once __DIR__ . '/../src/Views/pessoas/index.php';
