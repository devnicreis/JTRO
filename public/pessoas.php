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

function limparCepPessoa(string $cep): string
{
    return preg_replace('/\D+/', '', $cep);
}

function normalizarTextoPessoa(string $valor): string
{
    return preg_replace('/\s+/', ' ', trim($valor));
}

function normalizarCpfPessoa(string $cpf): string
{
    return preg_replace('/\D+/', '', $cpf);
}

function calcularIdadePessoa(string $data): ?int
{
    if ($data === '') {
        return null;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $data);
    if ($dt === false || $dt->format('Y-m-d') !== $data) {
        return null;
    }

    return $dt->diff(new DateTime('today'))->y;
}

function emailMenorPermitidoPorResponsavelPessoa(string $email, array $dadosResponsaveis): bool
{
    $emailNormalizado = mb_strtolower(trim($email));
    if ($emailNormalizado === '') {
        return false;
    }

    foreach (['responsavel_1', 'responsavel_2'] as $chaveResponsavel) {
        $responsavel = $dadosResponsaveis[$chaveResponsavel] ?? null;
        if (!is_array($responsavel)) {
            continue;
        }

        $emailResponsavel = mb_strtolower(trim((string) ($responsavel['email'] ?? '')));
        if ($emailResponsavel !== '' && $emailResponsavel === $emailNormalizado) {
            return true;
        }
    }

    return false;
}

function resolverDadosResponsaveisPessoa(PessoaRepository $repo, array $fonte, ?int $pessoaIdIgnorado = null): array
{
    $responsavel1Cpf = normalizarCpfPessoa((string) ($fonte['responsavel_1_cpf'] ?? ''));
    $responsavel1Nome = normalizarNomePessoa((string) ($fonte['responsavel_1_nome'] ?? ''));
    $responsavel2Cpf = normalizarCpfPessoa((string) ($fonte['responsavel_2_cpf'] ?? ''));
    $responsavel2Nome = normalizarNomePessoa((string) ($fonte['responsavel_2_nome'] ?? ''));
    $segundoResponsavelMarcado = isset($fonte['adicionar_segundo_responsavel']) && (string) $fonte['adicionar_segundo_responsavel'] === '1';
    $segundoResponsavelAtivo = $segundoResponsavelMarcado || $responsavel2Cpf !== '' || $responsavel2Nome !== '';

    $responsavel1PessoaId = null;
    $responsavel1 = null;
    if ($responsavel1Cpf !== '') {
        $responsavel = $repo->buscarResponsavelPorCpf($responsavel1Cpf, $pessoaIdIgnorado);
        if ($responsavel !== null) {
            $responsavel1 = $responsavel;
            $responsavel1PessoaId = (int) $responsavel['id'];
            $responsavel1Nome = normalizarNomePessoa((string) ($responsavel['nome'] ?? ''));
        }
    }

    $responsavel2PessoaId = null;
    $responsavel2 = null;
    if ($segundoResponsavelAtivo && $responsavel2Cpf !== '') {
        $responsavel = $repo->buscarResponsavelPorCpf($responsavel2Cpf, $pessoaIdIgnorado);
        if ($responsavel !== null) {
            $responsavel2 = $responsavel;
            $responsavel2PessoaId = (int) $responsavel['id'];
            $responsavel2Nome = normalizarNomePessoa((string) ($responsavel['nome'] ?? ''));
        }
    }

    return [
        'responsavel_1_cpf' => $responsavel1Cpf,
        'responsavel_1_nome' => $responsavel1Nome,
        'responsavel_1_pessoa_id' => $responsavel1PessoaId,
        'responsavel_1' => $responsavel1,
        'responsavel_2_cpf' => $segundoResponsavelAtivo ? $responsavel2Cpf : '',
        'responsavel_2_nome' => $segundoResponsavelAtivo ? $responsavel2Nome : '',
        'responsavel_2_pessoa_id' => $segundoResponsavelAtivo ? $responsavel2PessoaId : null,
        'responsavel_2' => $segundoResponsavelAtivo ? $responsavel2 : null,
        'adicionar_segundo_responsavel' => $segundoResponsavelAtivo ? '1' : '0',
    ];
}

function preencherContatoEnderecoDeMenorComResponsavel(array $fonte, ?array $responsavel, bool $menorDeIdade): array
{
    if (!$menorDeIdade || $responsavel === null) {
        return $fonte;
    }

    $campos = [
        'email',
        'telefone_fixo',
        'telefone_movel',
        'endereco_cep',
        'endereco_logradouro',
        'endereco_numero',
        'endereco_complemento',
        'endereco_bairro',
        'endereco_cidade',
        'endereco_uf',
    ];

    foreach ($campos as $campo) {
        $valorAtual = trim((string) ($fonte[$campo] ?? ''));
        $valorResponsavel = trim((string) ($responsavel[$campo] ?? ''));
        if ($valorAtual === '' && $valorResponsavel !== '') {
            $fonte[$campo] = $valorResponsavel;
        }
    }

    return $fonte;
}

function resolverDadosConjugePessoa(PessoaRepository $repo, array $fonte, ?int $pessoaIdIgnorado = null): array
{
    $conjugeCpf = normalizarCpfPessoa((string) ($fonte['conjuge_cpf'] ?? ''));
    $conjugeNome = normalizarNomePessoa((string) ($fonte['nome_conjuge'] ?? ''));
    $conjugePessoaId = null;

    if ($conjugeCpf !== '') {
        $conjuge = $repo->buscarResponsavelPorCpf($conjugeCpf, $pessoaIdIgnorado);
        if ($conjuge !== null) {
            $conjugePessoaId = (int) $conjuge['id'];
            $conjugeNome = normalizarNomePessoa((string) ($conjuge['nome'] ?? ''));
        }
    }

    return [
        'conjuge_cpf' => $conjugeCpf,
        'nome_conjuge' => $conjugeNome,
        'conjuge_pessoa_id' => $conjugePessoaId,
    ];
}

function validarTelefoneFixoPessoa(string $telefone): bool
{
    return $telefone === '' || in_array(strlen($telefone), [10, 11], true);
}

function validarTelefoneMovelPessoa(string $telefone): bool
{
    return $telefone === '' || strlen($telefone) === 11;
}

function validarCepPessoa(string $cep): bool
{
    return strlen($cep) === 8;
}

function validarUfPessoa(string $uf): bool
{
    $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
    return in_array(strtoupper($uf), $ufs, true);
}

$repo = new PessoaRepository();
$grupoRepo = new GrupoFamiliarRepository();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = normalizarNomePessoa($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $cargo = $_POST['cargo'] ?? '';
    $genero = trim($_POST['genero'] ?? '');
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $idade = calcularIdadePessoa($dataNascimento);
    $estadoCivil = $_POST['estado_civil'] ?? '';
    $ehLider = isset($_POST['eh_lider']) ? 1 : 0;
    $liderGrupoFamiliar = isset($_POST['lider_grupo_familiar']) ? 1 : 0;
    $liderDepartamento = isset($_POST['lider_departamento']) ? 1 : 0;
    $grupoFamiliarId = (int) ($_POST['grupo_familiar_id'] ?? 0);
    $concluiuIntegracao = (int) ($_POST['concluiu_integracao'] ?? -1);
    $participouRetiroIntegracao = (int) ($_POST['participou_retiro_integracao'] ?? -1);
    $dadosResponsaveis = resolverDadosResponsaveisPessoa($repo, $_POST);
    $dadosConjuge = resolverDadosConjugePessoa($repo, $_POST);
    $menorDeIdade = $idade !== null && $idade < 18;

    $_POST = preencherContatoEnderecoDeMenorComResponsavel($_POST, $dadosResponsaveis['responsavel_1'] ?? null, $menorDeIdade);

    $_POST['responsavel_1_cpf'] = $dadosResponsaveis['responsavel_1_cpf'];
    $_POST['responsavel_1_nome'] = $dadosResponsaveis['responsavel_1_nome'];
    $_POST['responsavel_2_cpf'] = $dadosResponsaveis['responsavel_2_cpf'];
    $_POST['responsavel_2_nome'] = $dadosResponsaveis['responsavel_2_nome'];
    $_POST['adicionar_segundo_responsavel'] = $dadosResponsaveis['adicionar_segundo_responsavel'];
    $_POST['conjuge_cpf'] = $dadosConjuge['conjuge_cpf'];
    $_POST['nome_conjuge'] = $dadosConjuge['nome_conjuge'];

    $email = trim($_POST['email'] ?? '');
    $telefoneFixo = limparTelefonePessoa($_POST['telefone_fixo'] ?? '');
    $telefoneMovel = limparTelefonePessoa($_POST['telefone_movel'] ?? '');
    $enderecoCep = limparCepPessoa($_POST['endereco_cep'] ?? '');
    $enderecoLogradouro = normalizarTextoPessoa($_POST['endereco_logradouro'] ?? '');
    $enderecoNumero = normalizarTextoPessoa($_POST['endereco_numero'] ?? '');
    $enderecoComplemento = normalizarTextoPessoa($_POST['endereco_complemento'] ?? '');
    $enderecoBairro = normalizarTextoPessoa($_POST['endereco_bairro'] ?? '');
    $enderecoCidade = normalizarTextoPessoa($_POST['endereco_cidade'] ?? '');
    $enderecoUf = strtoupper(normalizarTextoPessoa($_POST['endereco_uf'] ?? ''));

    $estadosValidos = ['solteiro', 'casado', 'uniao_estavel', 'divorciado', 'viuvo'];
    $generosValidos = ['masculino', 'feminino'];
    $requerConjuge = in_array($estadoCivil, ['casado', 'uniao_estavel'], true);

    if (
        $nome === '' || $cpf === '' || $cargo === '' || $genero === '' || $dataNascimento === '' || $estadoCivil === ''
        || $concluiuIntegracao < 0 || $participouRetiroIntegracao < 0
    ) {
        $erro = 'Preencha todos os campos obrigatorios do cadastro.';
    } elseif (!preg_match('/^[\p{L}\s]+$/u', $nome)) {
        $erro = 'O nome deve conter apenas letras e espacos.';
    } elseif (!ctype_digit($cpf)) {
        $erro = 'O CPF deve conter somente numeros, sem pontos e tracos.';
    } elseif (strlen($cpf) !== 11) {
        $erro = 'O CPF deve conter exatamente 11 digitos.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail valido.';
    } elseif (
        $email !== ''
        && ($emailExistente = $repo->buscarPorEmail($email)) !== null
        && (
            !$menorDeIdade
            || !emailMenorPermitidoPorResponsavelPessoa($email, $dadosResponsaveis)
        )
    ) {
        $erro = 'Ja existe uma pessoa cadastrada com esse e-mail.';
    } elseif (!in_array($genero, $generosValidos, true)) {
        $erro = 'Selecione um genero valido.';
    } elseif (!validarDataNascimentoPessoa($dataNascimento)) {
        $erro = 'Informe uma data de nascimento valida.';
    } elseif ($menorDeIdade && $dadosResponsaveis['responsavel_1_cpf'] === '') {
        $erro = 'Informe o CPF do responsavel principal.';
    } elseif ($menorDeIdade && strlen($dadosResponsaveis['responsavel_1_cpf']) !== 11) {
        $erro = 'O CPF do responsavel principal deve conter 11 digitos.';
    } elseif ($menorDeIdade && $dadosResponsaveis['responsavel_1_cpf'] === $cpf) {
        $erro = 'O responsavel principal nao pode ser a propria pessoa cadastrada.';
    } elseif ($menorDeIdade && $dadosResponsaveis['responsavel_1_nome'] === '') {
        $erro = 'Informe o nome do responsavel principal.';
    } elseif ($dadosResponsaveis['adicionar_segundo_responsavel'] === '1' && $dadosResponsaveis['responsavel_2_cpf'] === '') {
        $erro = 'Informe o CPF do segundo responsavel.';
    } elseif ($dadosResponsaveis['adicionar_segundo_responsavel'] === '1' && strlen($dadosResponsaveis['responsavel_2_cpf']) !== 11) {
        $erro = 'O CPF do segundo responsavel deve conter 11 digitos.';
    } elseif ($dadosResponsaveis['adicionar_segundo_responsavel'] === '1' && $dadosResponsaveis['responsavel_2_cpf'] === $cpf) {
        $erro = 'O segundo responsavel nao pode ser a propria pessoa cadastrada.';
    } elseif ($dadosResponsaveis['adicionar_segundo_responsavel'] === '1' && $dadosResponsaveis['responsavel_2_nome'] === '') {
        $erro = 'Informe o nome do segundo responsavel.';
    } elseif ($dadosResponsaveis['adicionar_segundo_responsavel'] === '1' && $dadosResponsaveis['responsavel_2_cpf'] === $dadosResponsaveis['responsavel_1_cpf']) {
        $erro = 'Os dois responsaveis devem ser diferentes.';
    } elseif (!in_array($estadoCivil, $estadosValidos, true)) {
        $erro = 'Selecione um estado civil valido.';
    } elseif ($requerConjuge && $dadosConjuge['conjuge_cpf'] === '') {
        $erro = 'Informe o CPF do conjuge/companheiro para o estado civil selecionado.';
    } elseif ($requerConjuge && strlen($dadosConjuge['conjuge_cpf']) !== 11) {
        $erro = 'O CPF do conjuge/companheiro deve conter 11 digitos.';
    } elseif ($requerConjuge && $dadosConjuge['conjuge_cpf'] === $cpf) {
        $erro = 'O CPF do conjuge/companheiro nao pode ser igual ao da propria pessoa cadastrada.';
    } elseif ($requerConjuge && $dadosConjuge['nome_conjuge'] === '') {
        $erro = 'Informe o nome do conjuge/companheiro.';
    } elseif ($dadosConjuge['nome_conjuge'] !== '' && !preg_match('/^[\p{L}\s]+$/u', $dadosConjuge['nome_conjuge'])) {
        $erro = 'O nome do conjuge/companheiro deve conter apenas letras e espacos.';
    } elseif (!validarTelefoneFixoPessoa($telefoneFixo)) {
        $erro = 'Informe um telefone fixo valido com DDD.';
    } elseif (!validarTelefoneMovelPessoa($telefoneMovel)) {
        $erro = 'Informe um telefone movel valido com DDD.';
    } elseif (!validarCepPessoa($enderecoCep) || $enderecoLogradouro === '' || $enderecoNumero === '' || $enderecoBairro === '' || $enderecoCidade === '' || $enderecoUf === '') {
        $erro = 'Preencha o endereco completo da pessoa.';
    } elseif (!validarUfPessoa($enderecoUf)) {
        $erro = 'Selecione uma UF valida para o endereco.';
    } elseif ($ehLider === 1 && $liderGrupoFamiliar === 0 && $liderDepartamento === 0) {
        $erro = 'Marque ao menos uma funcao de lideranca.';
    } else {
        $pessoaExistente = $repo->buscarPorCpf($cpf);

        if ($pessoaExistente !== null) {
            if ((int) $pessoaExistente['ativo'] === 0) {
                $erro = 'Usuario desativado. Por favor, contate a administracao.';
            } else {
                $erro = 'Ja existe uma pessoa cadastrada com esse CPF.';
            }
        } else {
            try {
                $pessoa = new Pessoa($nome, $cpf, $cargo);
                $repo->salvar($pessoa, [
                    'email' => $email,
                    'genero' => $genero,
                    'data_nascimento' => $dataNascimento,
                    'estado_civil' => $estadoCivil,
                    'nome_conjuge' => $requerConjuge ? $dadosConjuge['nome_conjuge'] : null,
                    'conjuge_cpf' => $requerConjuge ? $dadosConjuge['conjuge_cpf'] : null,
                    'conjuge_pessoa_id' => $requerConjuge ? $dadosConjuge['conjuge_pessoa_id'] : null,
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
                    'responsavel_1_cpf' => $menorDeIdade ? $dadosResponsaveis['responsavel_1_cpf'] : null,
                    'responsavel_1_nome' => $menorDeIdade ? $dadosResponsaveis['responsavel_1_nome'] : null,
                    'responsavel_1_pessoa_id' => $menorDeIdade ? $dadosResponsaveis['responsavel_1_pessoa_id'] : null,
                    'responsavel_2_cpf' => $menorDeIdade ? ($dadosResponsaveis['responsavel_2_cpf'] !== '' ? $dadosResponsaveis['responsavel_2_cpf'] : null) : null,
                    'responsavel_2_nome' => $menorDeIdade ? ($dadosResponsaveis['responsavel_2_nome'] !== '' ? $dadosResponsaveis['responsavel_2_nome'] : null) : null,
                    'responsavel_2_pessoa_id' => $menorDeIdade ? $dadosResponsaveis['responsavel_2_pessoa_id'] : null,
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
    'telefone_fixo' => trim($_GET['telefone_fixo'] ?? ''),
    'telefone_movel' => trim($_GET['telefone_movel'] ?? ''),
    'contato' => trim($_GET['contato'] ?? ''),
    'endereco' => trim($_GET['endereco'] ?? ''),
    'cargo' => trim($_GET['cargo'] ?? ''),
    'genero' => trim($_GET['genero'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'data_nascimento' => trim($_GET['data_nascimento'] ?? ''),
    'estado_civil' => trim($_GET['estado_civil'] ?? ''),
    'nome_conjuge' => trim($_GET['nome_conjuge'] ?? ''),
    'eh_lider' => trim($_GET['eh_lider'] ?? ''),
    'lider_grupo_familiar' => trim($_GET['lider_grupo_familiar'] ?? ''),
    'lider_departamento' => trim($_GET['lider_departamento'] ?? ''),
    'lideranca' => trim($_GET['lideranca'] ?? ''),
    'grupo_familiar_id' => trim($_GET['grupo_familiar_id'] ?? ''),
    'concluiu_integracao' => trim($_GET['concluiu_integracao'] ?? ''),
    'participou_retiro_integracao' => trim($_GET['participou_retiro_integracao'] ?? ''),
];

$pessoas = $repo->listarTodos($filtros);
$gruposFamiliares = $grupoRepo->listarAtivos();
$pageTitle = 'Cadastro de Pessoas - JTRO';

require_once __DIR__ . '/../src/Views/pessoas/cadastro.php';
