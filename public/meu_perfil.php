<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/PrivacySettings.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

Auth::requireLogin();

function normalizarNomeMeuPerfil(string $nome): string
{
    return preg_replace('/\s+/', ' ', trim($nome));
}

function normalizarTextoMeuPerfil(string $valor): string
{
    return preg_replace('/\s+/', ' ', trim($valor));
}

function normalizarCpfMeuPerfil(string $cpf): string
{
    return preg_replace('/\D+/', '', $cpf);
}

function limparTelefoneMeuPerfil(string $telefone): string
{
    return preg_replace('/\D+/', '', $telefone);
}

function limparCepMeuPerfil(string $cep): string
{
    return preg_replace('/\D+/', '', $cep);
}

function validarDataNascimentoMeuPerfil(string $data): bool
{
    if ($data === '') {
        return false;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt !== false && $dt->format('Y-m-d') === $data && $dt <= new DateTime('today');
}

function calcularIdadeMeuPerfil(string $data): ?int
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

function validarUfMeuPerfil(string $uf): bool
{
    $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
    return in_array(strtoupper($uf), $ufs, true);
}

function emailMenorPermitidoPorResponsavelMeuPerfil(string $email, array $dadosResponsaveis): bool
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

function resolverDadosResponsaveisMeuPerfil(PessoaRepository $repo, array $fonte, int $pessoaIdIgnorado): array
{
    $responsavel1Cpf = normalizarCpfMeuPerfil((string) ($fonte['responsavel_1_cpf'] ?? ''));
    $responsavel1Nome = normalizarNomeMeuPerfil((string) ($fonte['responsavel_1_nome'] ?? ''));
    $responsavel2Cpf = normalizarCpfMeuPerfil((string) ($fonte['responsavel_2_cpf'] ?? ''));
    $responsavel2Nome = normalizarNomeMeuPerfil((string) ($fonte['responsavel_2_nome'] ?? ''));
    $segundoResponsavelMarcado = isset($fonte['adicionar_segundo_responsavel']) && (string) $fonte['adicionar_segundo_responsavel'] === '1';
    $segundoResponsavelAtivo = $segundoResponsavelMarcado || $responsavel2Cpf !== '' || $responsavel2Nome !== '';

    $responsavel1PessoaId = null;
    $responsavel1 = null;
    if ($responsavel1Cpf !== '') {
        $responsavel = $repo->buscarResponsavelPorCpf($responsavel1Cpf, $pessoaIdIgnorado);
        if ($responsavel !== null) {
            $responsavel1 = $responsavel;
            $responsavel1PessoaId = (int) $responsavel['id'];
            $responsavel1Nome = normalizarNomeMeuPerfil((string) ($responsavel['nome'] ?? ''));
        }
    }

    $responsavel2PessoaId = null;
    $responsavel2 = null;
    if ($segundoResponsavelAtivo && $responsavel2Cpf !== '') {
        $responsavel = $repo->buscarResponsavelPorCpf($responsavel2Cpf, $pessoaIdIgnorado);
        if ($responsavel !== null) {
            $responsavel2 = $responsavel;
            $responsavel2PessoaId = (int) $responsavel['id'];
            $responsavel2Nome = normalizarNomeMeuPerfil((string) ($responsavel['nome'] ?? ''));
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

function preencherContatoEnderecoDeMenorComResponsavelMeuPerfil(array $fonte, ?array $responsavel, bool $menorDeIdade): array
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

function resolverDadosConjugeMeuPerfil(PessoaRepository $repo, array $fonte, int $pessoaIdIgnorado): array
{
    $conjugeCpf = normalizarCpfMeuPerfil((string) ($fonte['conjuge_cpf'] ?? ''));
    $conjugeNome = normalizarNomeMeuPerfil((string) ($fonte['nome_conjuge'] ?? ''));
    $conjugePessoaId = null;

    if ($conjugeCpf !== '') {
        $conjuge = $repo->buscarResponsavelPorCpf($conjugeCpf, $pessoaIdIgnorado);
        if ($conjuge !== null) {
            $conjugePessoaId = (int) $conjuge['id'];
            $conjugeNome = normalizarNomeMeuPerfil((string) ($conjuge['nome'] ?? ''));
        }
    }

    return [
        'conjuge_cpf' => $conjugeCpf,
        'nome_conjuge' => $conjugeNome,
        'conjuge_pessoa_id' => $conjugePessoaId,
    ];
}

$pageTitle = 'Meu Perfil - JTRO';
$repo = new PessoaRepository();
$auditoria = new AuditoriaService();
$usuarioSessao = Auth::usuario();
$usuarioId = Auth::id();

$mensagem = '';
$erro = '';
$erroSenha = '';

$pessoa = $repo->buscarPorId($usuarioId);

if (!$pessoa) {
    Auth::logout();
    header('Location: /login.php');
    exit;
}

$forcarTroca = Auth::precisaTrocarSenha() || (isset($_GET['forcar_troca']) && $_GET['forcar_troca'] === '1');
$destacarTrocaSenha = $forcarTroca;
$privacidadeAceitaAtual = PrivacySettings::consentimentoAtual($pessoa);
$privacidadeAceitaEm = $pessoa['privacidade_aceita_em'] ?? null;
$termosVersaoAceita = $pessoa['termos_versao_aceita'] ?? null;
$politicaVersaoAceita = $pessoa['politica_versao_aceita'] ?? null;
$supportContact = PrivacySettings::supportContact();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'atualizar_perfil') {
        $nome = normalizarNomeMeuPerfil((string) ($_POST['nome'] ?? ''));
        $cpf = normalizarCpfMeuPerfil((string) ($_POST['cpf'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $genero = trim((string) ($_POST['genero'] ?? ''));
        $dataNascimento = trim((string) ($_POST['data_nascimento'] ?? ''));
        $estadoCivil = trim((string) ($_POST['estado_civil'] ?? ''));
        $idade = calcularIdadeMeuPerfil($dataNascimento);
        $menorDeIdade = $idade !== null && $idade < 18;

        $dadosResponsaveis = resolverDadosResponsaveisMeuPerfil($repo, $_POST, $usuarioId);
        $dadosConjuge = resolverDadosConjugeMeuPerfil($repo, $_POST, $usuarioId);

        $_POST = preencherContatoEnderecoDeMenorComResponsavelMeuPerfil($_POST, $dadosResponsaveis['responsavel_1'] ?? null, $menorDeIdade);

        $_POST['responsavel_1_cpf'] = $dadosResponsaveis['responsavel_1_cpf'];
        $_POST['responsavel_1_nome'] = $dadosResponsaveis['responsavel_1_nome'];
        $_POST['responsavel_2_cpf'] = $dadosResponsaveis['responsavel_2_cpf'];
        $_POST['responsavel_2_nome'] = $dadosResponsaveis['responsavel_2_nome'];
        $_POST['adicionar_segundo_responsavel'] = $dadosResponsaveis['adicionar_segundo_responsavel'];
        $_POST['conjuge_cpf'] = $dadosConjuge['conjuge_cpf'];
        $_POST['nome_conjuge'] = $dadosConjuge['nome_conjuge'];

        $telefoneFixo = limparTelefoneMeuPerfil((string) ($_POST['telefone_fixo'] ?? ''));
        $telefoneMovel = limparTelefoneMeuPerfil((string) ($_POST['telefone_movel'] ?? ''));
        $enderecoCep = limparCepMeuPerfil((string) ($_POST['endereco_cep'] ?? ''));
        $enderecoLogradouro = normalizarTextoMeuPerfil((string) ($_POST['endereco_logradouro'] ?? ''));
        $enderecoNumero = normalizarTextoMeuPerfil((string) ($_POST['endereco_numero'] ?? ''));
        $enderecoComplemento = normalizarTextoMeuPerfil((string) ($_POST['endereco_complemento'] ?? ''));
        $enderecoBairro = normalizarTextoMeuPerfil((string) ($_POST['endereco_bairro'] ?? ''));
        $enderecoCidade = normalizarTextoMeuPerfil((string) ($_POST['endereco_cidade'] ?? ''));
        $enderecoUf = strtoupper(normalizarTextoMeuPerfil((string) ($_POST['endereco_uf'] ?? '')));

        $estadosValidos = ['solteiro', 'casado', 'uniao_estavel', 'divorciado', 'viuvo'];
        $generosValidos = ['masculino', 'feminino'];
        $requerConjuge = in_array($estadoCivil, ['casado', 'uniao_estavel'], true);

        if ($nome === '' || $cpf === '' || $genero === '' || $dataNascimento === '' || $estadoCivil === '') {
            $erro = 'Preencha todos os campos obrigatorios do cadastro.';
        } elseif (!preg_match('/^[\p{L}\s]+$/u', $nome)) {
            $erro = 'O nome deve conter apenas letras e espacos.';
        } elseif (!ctype_digit($cpf)) {
            $erro = 'O CPF deve conter somente numeros, sem pontos e tracos.';
        } elseif (strlen($cpf) !== 11) {
            $erro = 'O CPF deve conter exatamente 11 digitos.';
        } elseif ($repo->buscarPorCpfExcetoId($cpf, $usuarioId) !== null) {
            $erro = 'Ja existe outra pessoa cadastrada com esse CPF.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um e-mail valido.';
        } elseif (
            $email !== ''
            && ($emailExistente = $repo->buscarPorEmailExcetoId($email, $usuarioId)) !== null
            && (
                !$menorDeIdade
                || !emailMenorPermitidoPorResponsavelMeuPerfil($email, $dadosResponsaveis)
            )
        ) {
            $erro = 'Ja existe outra pessoa cadastrada com esse e-mail.';
        } elseif (!in_array($genero, $generosValidos, true)) {
            $erro = 'Selecione um genero valido.';
        } elseif (!validarDataNascimentoMeuPerfil($dataNascimento)) {
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
        } elseif ($telefoneFixo !== '' && !in_array(strlen($telefoneFixo), [10, 11], true)) {
            $erro = 'Informe um telefone fixo valido com DDD.';
        } elseif ($telefoneMovel !== '' && strlen($telefoneMovel) !== 11) {
            $erro = 'Informe um telefone movel valido com DDD.';
        } elseif ($enderecoCep !== '' && strlen($enderecoCep) !== 8) {
            $erro = 'Informe um CEP valido com 8 digitos.';
        } elseif ($enderecoUf !== '' && !validarUfMeuPerfil($enderecoUf)) {
            $erro = 'Selecione uma UF valida para o endereco.';
        } else {
            try {
                $repo->atualizar($usuarioId, [
                    'nome' => $nome,
                    'cpf' => $cpf,
                    'email' => $email !== '' ? $email : null,
                    'cargo' => $pessoa['cargo'],
                    'genero' => $genero,
                    'data_nascimento' => $dataNascimento,
                    'estado_civil' => $estadoCivil,
                    'nome_conjuge' => $requerConjuge ? $dadosConjuge['nome_conjuge'] : null,
                    'conjuge_cpf' => $requerConjuge ? $dadosConjuge['conjuge_cpf'] : null,
                    'conjuge_pessoa_id' => $requerConjuge ? $dadosConjuge['conjuge_pessoa_id'] : null,
                    'eh_lider' => (int) ($pessoa['eh_lider'] ?? 0),
                    'lider_grupo_familiar' => (int) ($pessoa['lider_grupo_familiar'] ?? 0),
                    'lider_departamento' => (int) ($pessoa['lider_departamento'] ?? 0),
                    'grupo_familiar_id' => $pessoa['grupo_familiar_id'] ?? null,
                    'telefone_fixo' => $telefoneFixo !== '' ? $telefoneFixo : null,
                    'telefone_movel' => $telefoneMovel !== '' ? $telefoneMovel : null,
                    'endereco_cep' => $enderecoCep !== '' ? $enderecoCep : null,
                    'endereco_logradouro' => $enderecoLogradouro !== '' ? $enderecoLogradouro : null,
                    'endereco_numero' => $enderecoNumero !== '' ? $enderecoNumero : null,
                    'endereco_complemento' => $enderecoComplemento !== '' ? $enderecoComplemento : null,
                    'endereco_bairro' => $enderecoBairro !== '' ? $enderecoBairro : null,
                    'endereco_cidade' => $enderecoCidade !== '' ? $enderecoCidade : null,
                    'endereco_uf' => $enderecoUf !== '' ? $enderecoUf : null,
                    'concluiu_integracao' => (int) ($pessoa['concluiu_integracao'] ?? 0),
                    'integracao_conclusao_manual' => (int) ($pessoa['integracao_conclusao_manual'] ?? 0),
                    'participou_retiro_integracao' => (int) ($pessoa['participou_retiro_integracao'] ?? 0),
                    'responsavel_1_cpf' => $menorDeIdade ? ($dadosResponsaveis['responsavel_1_cpf'] !== '' ? $dadosResponsaveis['responsavel_1_cpf'] : null) : null,
                    'responsavel_1_nome' => $menorDeIdade ? ($dadosResponsaveis['responsavel_1_nome'] !== '' ? $dadosResponsaveis['responsavel_1_nome'] : null) : null,
                    'responsavel_1_pessoa_id' => $menorDeIdade ? $dadosResponsaveis['responsavel_1_pessoa_id'] : null,
                    'responsavel_2_cpf' => $menorDeIdade ? ($dadosResponsaveis['responsavel_2_cpf'] !== '' ? $dadosResponsaveis['responsavel_2_cpf'] : null) : null,
                    'responsavel_2_nome' => $menorDeIdade ? ($dadosResponsaveis['responsavel_2_nome'] !== '' ? $dadosResponsaveis['responsavel_2_nome'] : null) : null,
                    'responsavel_2_pessoa_id' => $menorDeIdade ? $dadosResponsaveis['responsavel_2_pessoa_id'] : null,
                ]);

                $pessoa = $repo->buscarPorId($usuarioId);
                Auth::atualizarSessao($pessoa);
                $mensagem = 'Dados atualizados com sucesso.';
            } catch (Exception $e) {
                $erro = $e->getMessage();
            }
        }

        $pessoa = array_merge($pessoa, [
            'nome' => $nome,
            'cpf' => $cpf,
            'email' => $email,
            'genero' => $genero,
            'data_nascimento' => $dataNascimento,
            'estado_civil' => $estadoCivil,
            'nome_conjuge' => $requerConjuge ? $dadosConjuge['nome_conjuge'] : '',
            'conjuge_cpf' => $requerConjuge ? $dadosConjuge['conjuge_cpf'] : '',
            'telefone_fixo' => $telefoneFixo,
            'telefone_movel' => $telefoneMovel,
            'endereco_cep' => $enderecoCep,
            'endereco_logradouro' => $enderecoLogradouro,
            'endereco_numero' => $enderecoNumero,
            'endereco_complemento' => $enderecoComplemento,
            'endereco_bairro' => $enderecoBairro,
            'endereco_cidade' => $enderecoCidade,
            'endereco_uf' => $enderecoUf,
            'responsavel_1_cpf' => $menorDeIdade ? $dadosResponsaveis['responsavel_1_cpf'] : null,
            'responsavel_1_nome' => $menorDeIdade ? $dadosResponsaveis['responsavel_1_nome'] : null,
            'responsavel_2_cpf' => $menorDeIdade ? $dadosResponsaveis['responsavel_2_cpf'] : null,
            'responsavel_2_nome' => $menorDeIdade ? $dadosResponsaveis['responsavel_2_nome'] : null,
            'adicionar_segundo_responsavel' => $dadosResponsaveis['adicionar_segundo_responsavel'],
        ]);
    } elseif ($acao === 'atualizar_email') {
        $email = trim($_POST['email'] ?? '');

        if ($email === '') {
            $erro = 'Informe um e-mail.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um e-mail valido.';
        } elseif ($repo->buscarPorEmailExcetoId($email, $usuarioId) !== null) {
            $erro = 'Esse e-mail ja esta sendo usado por outra pessoa.';
        } else {
            try {
                $repo->atualizar($pessoa['id'], [
                    'nome' => $pessoa['nome'],
                    'cpf' => $pessoa['cpf'],
                    'email' => $email,
                    'cargo' => $pessoa['cargo'],
                    'genero' => $pessoa['genero'] ?? null,
                    'data_nascimento' => $pessoa['data_nascimento'] ?? null,
                    'estado_civil' => $pessoa['estado_civil'] ?? 'solteiro',
                    'nome_conjuge' => $pessoa['nome_conjuge'] ?? null,
                    'conjuge_cpf' => $pessoa['conjuge_cpf'] ?? null,
                    'conjuge_pessoa_id' => $pessoa['conjuge_pessoa_id'] ?? null,
                    'eh_lider' => (int) ($pessoa['eh_lider'] ?? 0),
                    'lider_grupo_familiar' => (int) ($pessoa['lider_grupo_familiar'] ?? 0),
                    'lider_departamento' => (int) ($pessoa['lider_departamento'] ?? 0),
                    'grupo_familiar_id' => $pessoa['grupo_familiar_id'] ?? null,
                    'telefone_fixo' => $pessoa['telefone_fixo'] ?? null,
                    'telefone_movel' => $pessoa['telefone_movel'] ?? null,
                    'endereco_cep' => $pessoa['endereco_cep'] ?? null,
                    'endereco_logradouro' => $pessoa['endereco_logradouro'] ?? null,
                    'endereco_numero' => $pessoa['endereco_numero'] ?? null,
                    'endereco_complemento' => $pessoa['endereco_complemento'] ?? null,
                    'endereco_bairro' => $pessoa['endereco_bairro'] ?? null,
                    'endereco_cidade' => $pessoa['endereco_cidade'] ?? null,
                    'endereco_uf' => $pessoa['endereco_uf'] ?? null,
                    'concluiu_integracao' => (int) ($pessoa['concluiu_integracao'] ?? 0),
                    'integracao_conclusao_manual' => (int) ($pessoa['integracao_conclusao_manual'] ?? 0),
                    'participou_retiro_integracao' => (int) ($pessoa['participou_retiro_integracao'] ?? 0),
                    'responsavel_1_cpf' => $pessoa['responsavel_1_cpf'] ?? null,
                    'responsavel_1_nome' => $pessoa['responsavel_1_nome'] ?? null,
                    'responsavel_1_pessoa_id' => $pessoa['responsavel_1_pessoa_id'] ?? null,
                    'responsavel_2_cpf' => $pessoa['responsavel_2_cpf'] ?? null,
                    'responsavel_2_nome' => $pessoa['responsavel_2_nome'] ?? null,
                    'responsavel_2_pessoa_id' => $pessoa['responsavel_2_pessoa_id'] ?? null,
                ]);
                $pessoa = $repo->buscarPorId($usuarioId);
                Auth::atualizarSessao($pessoa);
                $mensagem = 'E-mail atualizado com sucesso.';
            } catch (Exception $e) {
                $erro = $e->getMessage();
            }
        }
    } elseif ($acao === 'alterar_senha') {
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        if (!$forcarTroca && !password_verify($senhaAtual, $pessoa['senha_hash'] ?? '')) {
            $erroSenha = 'A senha atual esta incorreta.';
        } elseif ($novaSenha !== $confirmarSenha) {
            $erroSenha = 'A confirmacao da nova senha nao confere.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $novaSenha)) {
            $erroSenha = 'A nova senha deve ter pelo menos 8 caracteres, com letra minuscula, maiuscula, numero e simbolo.';
        } else {
            $repo->atualizarSenhaEObrigacao($usuarioId, $novaSenha, false);
            $pessoa = $repo->buscarPorId($usuarioId);
            Auth::atualizarSessao($pessoa);

            $auditoria->registrar(
                'alterar',
                'senha',
                $usuarioId,
                "Usuario alterou a propria senha.",
                null,
                null,
                null
            );

            if ($forcarTroca) {
                header('Location: /index.php?senha_alterada=1');
                exit;
            }

            $mensagem = 'Senha alterada com sucesso.';
        }
    }
}

require_once __DIR__ . '/../src/Views/pessoas/meu_perfil.php';
