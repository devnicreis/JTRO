<?php

require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

function normalizarNomePessoaEdicao(string $nome): string
{
    return preg_replace('/\s+/', ' ', trim($nome));
}

function validarDataNascimentoPessoaEdicao(string $data): bool
{
    if ($data === '') {
        return false;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt !== false && $dt->format('Y-m-d') === $data && $dt <= new DateTime('today');
}

function limparTelefonePessoaEdicao(string $telefone): string
{
    return preg_replace('/\D+/', '', $telefone);
}

function limparCepPessoaEdicao(string $cep): string
{
    return preg_replace('/\D+/', '', $cep);
}

function normalizarTextoPessoaEdicao(string $valor): string
{
    return preg_replace('/\s+/', ' ', trim($valor));
}

function validarCepPessoaEdicao(string $cep): bool
{
    return strlen($cep) === 8;
}

function validarUfPessoaEdicao(string $uf): bool
{
    $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
    return in_array(strtoupper($uf), $ufs, true);
}

$repo = new PessoaRepository();
$grupoRepo = new GrupoFamiliarRepository();
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
    $nome = normalizarNomePessoaEdicao($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cargo = $_POST['cargo'] ?? '';
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $estadoCivil = $_POST['estado_civil'] ?? '';
    $nomeConjuge = normalizarNomePessoaEdicao($_POST['nome_conjuge'] ?? '');
    $ehLider = isset($_POST['eh_lider']) ? 1 : 0;
    $liderGrupoFamiliar = isset($_POST['lider_grupo_familiar']) ? 1 : 0;
    $liderDepartamento = isset($_POST['lider_departamento']) ? 1 : 0;
    $grupoFamiliarId = (int) ($_POST['grupo_familiar_id'] ?? 0);
    $telefoneFixo = limparTelefonePessoaEdicao($_POST['telefone_fixo'] ?? '');
    $telefoneMovel = limparTelefonePessoaEdicao($_POST['telefone_movel'] ?? '');
    $enderecoCep = limparCepPessoaEdicao($_POST['endereco_cep'] ?? '');
    $enderecoLogradouro = normalizarTextoPessoaEdicao($_POST['endereco_logradouro'] ?? '');
    $enderecoNumero = normalizarTextoPessoaEdicao($_POST['endereco_numero'] ?? '');
    $enderecoComplemento = normalizarTextoPessoaEdicao($_POST['endereco_complemento'] ?? '');
    $enderecoBairro = normalizarTextoPessoaEdicao($_POST['endereco_bairro'] ?? '');
    $enderecoCidade = normalizarTextoPessoaEdicao($_POST['endereco_cidade'] ?? '');
    $enderecoUf = strtoupper(normalizarTextoPessoaEdicao($_POST['endereco_uf'] ?? ''));
    $concluiuIntegracao = (int) ($_POST['concluiu_integracao'] ?? -1);
    $participouRetiroIntegracao = (int) ($_POST['participou_retiro_integracao'] ?? -1);
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

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
    } elseif ($repo->buscarPorCpfExcetoId($cpf, $id) !== null) {
        $erro = 'Já existe outra pessoa cadastrada com esse CPF.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif ($email !== '' && $repo->buscarPorEmailExcetoId($email, $id) !== null) {
        $erro = 'Já existe outra pessoa cadastrada com esse e-mail.';
    } elseif (!validarDataNascimentoPessoaEdicao($dataNascimento)) {
        $erro = 'Informe uma data de nascimento válida.';
    } elseif (!in_array($estadoCivil, $estadosValidos, true)) {
        $erro = 'Selecione um estado civil válido.';
    } elseif (in_array($estadoCivil, ['casado', 'uniao_estavel'], true) && $nomeConjuge === '') {
        $erro = 'Informe o nome do cônjuge para o estado civil selecionado.';
    } elseif ($nomeConjuge !== '' && !preg_match('/^[\p{L}\s]+$/u', $nomeConjuge)) {
        $erro = 'O nome do cônjuge deve conter apenas letras e espaços.';
    } elseif ($telefoneFixo !== '' && !in_array(strlen($telefoneFixo), [10, 11], true)) {
        $erro = 'Informe um telefone fixo válido com DDD.';
    } elseif ($telefoneMovel !== '' && strlen($telefoneMovel) !== 11) {
        $erro = 'Informe um telefone móvel válido com DDD.';
    } elseif (!validarCepPessoaEdicao($enderecoCep) || $enderecoLogradouro === '' || $enderecoNumero === '' || $enderecoBairro === '' || $enderecoCidade === '' || $enderecoUf === '') {
        $erro = 'Preencha o endereço completo da pessoa.';
    } elseif (!validarUfPessoaEdicao($enderecoUf)) {
        $erro = 'Selecione uma UF válida para o endereço.';
    } elseif ($ehLider === 1 && $liderGrupoFamiliar === 0 && $liderDepartamento === 0) {
        $erro = 'Marque ao menos uma função de liderança.';
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
                $repo->atualizar($id, [
                    'nome' => $nome,
                    'cpf' => $cpf,
                    'email' => $email,
                    'cargo' => $cargo,
                    'data_nascimento' => $dataNascimento,
                    'estado_civil' => $estadoCivil,
                    'nome_conjuge' => in_array($estadoCivil, ['casado', 'uniao_estavel'], true) ? $nomeConjuge : null,
                    'eh_lider' => $ehLider,
                    'lider_grupo_familiar' => $ehLider ? $liderGrupoFamiliar : 0,
                    'lider_departamento' => $ehLider ? $liderDepartamento : 0,
                    'grupo_familiar_id' => $grupoFamiliarId > 0 ? $grupoFamiliarId : null,
                    'telefone_fixo' => $telefoneFixo,
                    'telefone_movel' => $telefoneMovel,
                    'endereco_cep' => $enderecoCep,
                    'endereco_logradouro' => $enderecoLogradouro,
                    'endereco_numero' => $enderecoNumero,
                    'endereco_complemento' => $enderecoComplemento !== '' ? $enderecoComplemento : null,
                    'endereco_bairro' => $enderecoBairro,
                    'endereco_cidade' => $enderecoCidade,
                    'endereco_uf' => $enderecoUf,
                    'concluiu_integracao' => $concluiuIntegracao,
                    'integracao_conclusao_manual' => $concluiuIntegracao,
                    'participou_retiro_integracao' => $participouRetiroIntegracao,
                ]);

                $auditoria->registrar('atualizar', 'pessoa', $id, "Pessoa atualizada: {$nome}.", null, null, null);

                if ($novaSenha !== '') {
                    $repo->atualizarSenhaEObrigacao($id, $novaSenha, true);
                    $auditoria->registrar('redefinir', 'senha', $id, "Senha redefinida por administrador para {$nome}.", null, null, null);
                }

                $mensagem = 'Pessoa atualizada com sucesso.';
                $pessoa = $repo->buscarPorId($id);
            } catch (Exception $e) {
                $erro = $e->getMessage();
            }
        }
    }
}

$gruposFamiliares = $grupoRepo->listarAtivos();
$pageTitle = 'Editar Pessoa - JTRO';

require_once __DIR__ . '/../src/Views/pessoas/editar.php';
