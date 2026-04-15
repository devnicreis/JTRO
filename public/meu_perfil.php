<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/PrivacySettings.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

Auth::requireLogin();

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
                $repo->atualizar($pessoa['id'], [
                    'nome' => $pessoa['nome'],
                    'cpf' => $pessoa['cpf'],
                    'email' => $email,
                    'cargo' => $pessoa['cargo'],
                    'data_nascimento' => $pessoa['data_nascimento'] ?? null,
                    'estado_civil' => $pessoa['estado_civil'] ?? 'solteiro',
                    'nome_conjuge' => $pessoa['nome_conjuge'] ?? null,
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
                ]);
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
            $erroSenha = 'A senha atual está incorreta.';
        } elseif ($novaSenha !== $confirmarSenha) {
            $erroSenha = 'A confirmação da nova senha não confere.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $novaSenha)) {
            $erroSenha = 'A nova senha deve ter pelo menos 8 caracteres, com letra minúscula, maiúscula, número e símbolo.';
        } else {
            $repo->atualizarSenhaEObrigacao($usuarioId, $novaSenha, false);
            $pessoa = $repo->buscarPorId($usuarioId);
            Auth::atualizarSessao($pessoa);

            $auditoria->registrar(
                'alterar',
                'senha',
                $usuarioId,
                "Usuário alterou a própria senha.",
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
