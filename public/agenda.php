<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/AgendaRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$repo    = new AgendaRepository();
$isAdmin = Auth::isAdmin();

$mensagem = '';
$erro     = '';

// POST: excluir (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $acao = $_POST['acao'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);
    if ($acao === 'excluir' && $id > 0) {
        $repo->excluir($id);
        $mensagem = 'Evento excluído.';
    }
}

// POST: importar ICS (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && isset($_FILES['ics'])) {
    $file = $_FILES['ics'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $conteudo = file_get_contents($file['tmp_name']);
        [$imp, $ign] = $repo->importarIcs($conteudo, Auth::id());
        $mensagem = "Importação concluída: {$imp} evento(s) importado(s), {$ign} ignorado(s) (duplicados ou inválidos).";
    } else {
        $erro = 'Erro ao fazer upload do arquivo.';
    }
}

// Parâmetros de navegação
$hoje     = new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
$anoAtual = (int)($hoje->format('Y'));
$mesAtual = (int)($hoje->format('m'));

$ano  = (int)($_GET['ano']  ?? $anoAtual);
$mes  = (int)($_GET['mes']  ?? $mesAtual);
$dpto = $_GET['dpto'] ?? '';
$diaSelecionado = $_GET['dia'] ?? $hoje->format('Y-m-d');

// Clamp
if ($mes < 1) { $mes = 12; $ano--; }
if ($mes > 12) { $mes = 1; $ano++; }

$diasComEventos = $repo->listarDiasComEventos($ano, $mes);
$eventosDia     = $repo->listarPorDia($diaSelecionado, $dpto ?: null);

$pageTitle = 'Agenda - JTRO';
require_once __DIR__ . '/../src/Views/agenda/index.php';
