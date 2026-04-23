<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/AgendaRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

const AGENDA_IMPORT_MAX_BYTES = 1048576;

$repo = new AgendaRepository();
$isAdmin = Auth::isAdmin();
$departamentosImportacao = AgendaRepository::DEPARTAMENTOS;
$departamentoImportacao = 'Pastoral';

$mensagem = '';
$erro = '';

// POST: excluir (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $acao = $_POST['acao'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($acao === 'excluir' && $id > 0) {
        $repo->excluir($id);
        $mensagem = 'Evento excluido.';
    }
}

// POST: importar ICS (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && isset($_FILES['ics'])) {
    $file = $_FILES['ics'];
    $departamentoImportacao = trim((string) ($_POST['departamento'] ?? 'Pastoral'));

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $nomeArquivo = strtolower((string) ($file['name'] ?? ''));
        $tamanhoArquivo = (int) ($file['size'] ?? 0);

        if (!str_ends_with($nomeArquivo, '.ics')) {
            $erro = 'Envie um arquivo .ics valido.';
        } elseif ($tamanhoArquivo <= 0 || $tamanhoArquivo > AGENDA_IMPORT_MAX_BYTES) {
            $erro = 'O arquivo .ics deve ter no maximo 1 MB.';
        } elseif (!in_array($departamentoImportacao, $departamentosImportacao, true)) {
            $erro = 'Selecione uma categoria valida para importar.';
        } else {
            $conteudo = file_get_contents((string) ($file['tmp_name'] ?? ''));

            if ($conteudo === false || trim($conteudo) === '') {
                $erro = 'Nao foi possivel ler o arquivo .ics enviado.';
            } else {
                try {
                    [$imp, $ign] = $repo->importarIcs($conteudo, Auth::id(), $departamentoImportacao);
                    $mensagem = "Importacao concluida: {$imp} evento(s) importado(s), {$ign} ignorado(s) (duplicados ou invalidos).";
                } catch (Throwable $e) {
                    $erro = $e->getMessage() !== '' ? $e->getMessage() : 'Erro ao importar o arquivo .ics.';
                }
            }
        }
    } else {
        $erro = 'Erro ao fazer upload do arquivo.';
    }
}

// Parametros de navegacao
$hoje = new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
$anoAtual = (int) ($hoje->format('Y'));
$mesAtual = (int) ($hoje->format('m'));

$ano = (int) ($_GET['ano'] ?? $anoAtual);
$mes = (int) ($_GET['mes'] ?? $mesAtual);
$dpto = $_GET['dpto'] ?? '';
$diaSelecionado = $_GET['dia'] ?? $hoje->format('Y-m-d');

// Clamp
if ($mes < 1) {
    $mes = 12;
    $ano--;
}

if ($mes > 12) {
    $mes = 1;
    $ano++;
}

$diasComEventos = $repo->listarDiasComEventos($ano, $mes);
$eventosDia = $repo->listarPorDia($diaSelecionado, $dpto ?: null);

$pageTitle = 'Agenda - JTRO';
require_once __DIR__ . '/../src/Views/agenda/index.php';
