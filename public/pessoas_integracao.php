<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$pessoaId = (int) ($_GET['id'] ?? 0);

if ($pessoaId <= 0) {
    http_response_code(400);
    die('Pessoa inválida.');
}

$pessoaRepo = new PessoaRepository();
$presencaRepo = new PresencaRepository();

$pessoa = $pessoaRepo->buscarPorId($pessoaId);
if (!$pessoa) {
    http_response_code(404);
    die('Pessoa não encontrada.');
}

$grupoId = (int) ($pessoa['grupo_familiar_id'] ?? 0);
$usuarioId = Auth::id();
$mensagem = '';
$erro = '';
$abrirModal = false;
$modalModo = 'novo';
$modalAulaCodigoOriginal = '';
$modalAulaCodigo = '';
$modalAulaTitulo = '';
$modalDataAula = '';
$aulasIntegracao = $presencaRepo->listarAulasIntegracaoCurriculo();

if (!Auth::isAdmin()) {
    if ($grupoId <= 0 || !$presencaRepo->liderPodeAcessarGrupo($usuarioId, $grupoId)) {
        http_response_code(403);
        die('Você não tem permissão para visualizar a integração desta pessoa.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['salvar_aula_manual'])) {
        $modalAulaCodigoOriginal = trim((string) ($_POST['aula_codigo_original'] ?? ''));
        $aulaCodigo = trim((string) ($_POST['aula_codigo'] ?? ''));
        $dataAula = trim((string) ($_POST['data_aula'] ?? ''));
        $modalModo = $aulaCodigo !== '' ? 'editar' : 'novo';
        if ($modalModo === 'novo' && $modalAulaCodigoOriginal !== '') {
            $modalModo = 'editar';
        }
        $modalAulaCodigoOriginal = $modalAulaCodigoOriginal !== '' ? $modalAulaCodigoOriginal : ($modalModo === 'editar' ? $aulaCodigo : '');
        $modalAulaCodigo = $aulaCodigo;
        $modalDataAula = $dataAula;
        $abrirModal = true;

        try {
            $presencaRepo->salvarAulaIntegracaoManual(
                $pessoaId,
                $modalAulaCodigoOriginal !== '' ? $modalAulaCodigoOriginal : null,
                $aulaCodigo !== '' ? $aulaCodigo : null,
                $dataAula
            );
            $mensagem = $modalAulaCodigoOriginal !== ''
                ? 'Aula manual atualizada com sucesso.'
                : 'Aula adicionada manualmente com sucesso.';
            $pessoa = $pessoaRepo->buscarPorId($pessoaId) ?: $pessoa;
            $abrirModal = false;
            $modalModo = 'novo';
            $modalAulaCodigoOriginal = '';
            $modalAulaCodigo = '';
            $modalAulaTitulo = '';
            $modalDataAula = '';
        } catch (InvalidArgumentException $e) {
            $erro = $e->getMessage();
        }
    }

    if (isset($_POST['remover_aula_manual'])) {
        $aulaCodigo = trim((string) ($_POST['aula_codigo'] ?? ''));

        try {
            $presencaRepo->removerAulaIntegracaoManual($pessoaId, $aulaCodigo);
            $mensagem = 'Aula manual removida com sucesso.';
            $pessoa = $pessoaRepo->buscarPorId($pessoaId) ?: $pessoa;
        } catch (InvalidArgumentException $e) {
            $erro = $e->getMessage();
        }
    }
}

$progresso = $presencaRepo->listarProgressoIntegracaoPessoa($pessoaId);
$totalConcluidas = 0;
$proximaAulaPendente = null;

foreach ($progresso as $aula) {
    if (!empty($aula['concluida'])) {
        $totalConcluidas++;
        continue;
    }

    if ($proximaAulaPendente === null) {
        $proximaAulaPendente = $aula;
    }
}

if ($abrirModal) {
    if ($modalModo === 'editar' && $modalAulaCodigo !== '') {
        foreach ($progresso as $aula) {
            if ((string) ($aula['codigo'] ?? '') === $modalAulaCodigo) {
                $modalAulaTitulo = (string) ($aula['titulo'] ?? '');
                break;
            }
        }
    } elseif ($modalModo === 'novo') {
        $modalAulaTitulo = (string) ($proximaAulaPendente['titulo'] ?? '');
    }
}

$pageTitle = 'Aulas de Integração - JTRO';

require_once __DIR__ . '/../src/Views/pessoas/integracao.php';
