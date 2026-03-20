<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

function dataValida(string $data): bool
{
    $dt = DateTime::createFromFormat('!Y-m-d', $data);
    return $dt !== false && $dt->format('Y-m-d') === $data;
}

function dataDentroDaJanelaPermitida(string $data): bool
{
    $tz   = new DateTimeZone('America/Sao_Paulo');
    $hoje = new DateTimeImmutable('today', $tz);
    $dt   = DateTimeImmutable::createFromFormat('!Y-m-d', $data, $tz);
    if ($dt === false || $dt->format('Y-m-d') !== $data) return false;
    return $dt >= $hoje->modify('-30 days') && $dt <= $hoje;
}

$repo      = new PresencaRepository();
$auditoria = new AuditoriaService();

$mensagem = '';
$erro     = '';
$grupoId  = 0;
$data     = '';
$reuniao  = null;
$listaPresencas  = [];
$membrosGrupo    = [];  // membros para formulário de nova reunião
$presencasPendentes = false;
$modoNovaReuniao = false; // true = reunião ainda não existe no banco

// ── Verificação de acesso ──────────────────────────────────
function verificarAcesso(PresencaRepository $repo, int $grupoId): void {
    if ($grupoId > 0 && !Auth::isAdmin()) {
        if (!$repo->liderPodeAcessarGrupo(Auth::id(), $grupoId)) {
            http_response_code(403);
            die('Acesso negado a este Grupo Familiar.');
        }
    }
}

// ── POST: Salvar reunião nova (criar + presenças atomicamente) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_reuniao_nova'])) {
    $grupoId     = (int) ($_POST['grupo_id'] ?? 0);
    $data        = trim($_POST['data'] ?? '');
    $horario     = trim($_POST['horario_criacao'] ?? '');
    $local       = trim($_POST['local'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $presencas   = $_POST['presencas'] ?? [];

    verificarAcesso($repo, $grupoId);

    try {
        if ($grupoId <= 0 || $data === '') throw new InvalidArgumentException('Grupo Familiar e data são obrigatórios.');
        if (!dataValida($data))             throw new InvalidArgumentException('Data inválida.');
        if (!dataDentroDaJanelaPermitida($data)) throw new InvalidArgumentException('A reunião só pode ser criada para hoje ou até 30 dias atrás.');
        if ($horario === '')                throw new InvalidArgumentException('Informe o horário da reunião.');
        if ($local === '')                  throw new InvalidArgumentException('Informe o local da reunião.');

        // Valida que todas as presenças foram marcadas
        $membros = $repo->listarMembrosPorGrupo($grupoId);
        if (count($presencas) !== count($membros)) {
            throw new InvalidArgumentException('Marque a presença ou ausência de todos os membros antes de salvar.');
        }

        $reuniaoId = $repo->criarReuniaoComPresencas($grupoId, $data, $horario, $local, $observacoes, $presencas);
        $mensagem  = 'Reunião salva com sucesso.';

        $reuniao        = $repo->buscarReuniao($reuniaoId);
        $listaPresencas = $repo->listarPresencasPorReuniao($reuniaoId);
        $presencasPendentes = false;

        $auditoria->registrar('criar', 'reuniao', $reuniaoId,
            "Reunião criada para o GF {$reuniao['grupo_nome']} em " . date('d/m/Y', strtotime($data)) . " às {$horario}.",
            null, $grupoId, $data);
        $auditoria->registrar('atualizar', 'presencas', $reuniaoId,
            "Presenças atualizadas para a reunião do GF {$reuniao['grupo_nome']} em " . date('d/m/Y', strtotime($data)) . ".",
            null, $grupoId, $data);

    } catch (Exception $e) {
        $erro = $e->getMessage();
        // Mantém o formulário de nova reunião visível com os dados preenchidos
        $modoNovaReuniao = true;
        $membrosGrupo    = $repo->listarMembrosPorGrupo($grupoId);
    }
}

// ── POST: Atualizar presenças de reunião já existente ──────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_presencas'])) {
    $reuniaoId   = (int) ($_POST['reuniao_id'] ?? 0);
    $grupoId     = (int) ($_POST['grupo_id'] ?? 0);
    $data        = trim($_POST['data'] ?? '');
    $local       = trim($_POST['local'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $presencas   = $_POST['presencas'] ?? [];

    verificarAcesso($repo, $grupoId);

    $totalEsperado = $repo->contarPresencasDaReuniao($reuniaoId);
    if (count($presencas) !== $totalEsperado) {
        $erro = 'Marque a presença ou ausência de todos os membros antes de salvar.';
    } elseif (mb_strlen($observacoes) > 255) {
        $erro = 'Observações devem ter no máximo 255 caracteres.';
    } else {
        try {
            $repo->atualizarPresencasEReuniao($reuniaoId, $local, $observacoes, $presencas);
            $mensagem = 'Reunião e presenças atualizadas com sucesso.';
            $reuniao  = $repo->buscarReuniao($reuniaoId);
            if ($reuniao) {
                $auditoria->registrar('atualizar', 'presencas', $reuniaoId,
                    "Presenças atualizadas para a reunião do GF {$reuniao['grupo_nome']} em " . date('d/m/Y', strtotime($data)) . ".",
                    null, $grupoId, $data);
            }
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
    if ($reuniaoId > 0) {
        $reuniao        = $reuniao ?? $repo->buscarReuniao($reuniaoId);
        $listaPresencas = $repo->listarPresencasPorReuniao($reuniaoId);
        $presencasPendentes = $repo->reuniaoTemPresencasPendentes($reuniaoId);
    }
}

// ── GET: Carregar reunião existente ou preparar nova ────────
$grupoId = $grupoId ?: (int) ($_GET['grupo_id'] ?? 0);
$data    = $data    ?: trim($_GET['data'] ?? '');

if ($grupoId > 0 && $data !== '' && $reuniao === null) {
    verificarAcesso($repo, $grupoId);
    if (!dataValida($data)) {
        $erro = 'Data inválida.';
    } else {
        try {
            $reuniaoId = $repo->buscarReuniaoPorGrupoEData($grupoId, $data);
            if ($reuniaoId !== null) {
                $reuniao        = $repo->buscarReuniao($reuniaoId);
                $listaPresencas = $repo->listarPresencasPorReuniao($reuniaoId);
                $presencasPendentes = $repo->reuniaoTemPresencasPendentes($reuniaoId);
            } else {
                // Reunião não existe: prepara modo de criação
                $modoNovaReuniao = true;
                $membrosGrupo    = $repo->listarMembrosPorGrupo($grupoId);
                $resumoGrupoHorario = $repo->buscarResumoGrupo($grupoId);
            }
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

if (Auth::isAdmin()) {
    $grupos = $repo->listarGruposFamiliares();
} else {
    $grupos = $repo->listarGruposFamiliaresPorLider(Auth::id());
}

$pageTitle = 'Reuniões e Presenças - JTRO';
require_once __DIR__ . '/../src/Views/presencas/index.php';
