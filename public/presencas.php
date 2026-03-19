<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

function dataValida(string $data): bool
{
    $dateTime = DateTime::createFromFormat('!Y-m-d', $data);

    return $dateTime !== false
        && $dateTime->format('Y-m-d') === $data;
}

function dataDentroDaJanelaPermitida(string $data): bool
{
    $timezone = new DateTimeZone('America/Sao_Paulo');

    $hoje = new DateTimeImmutable('today', $timezone);
    $dataInformada = DateTimeImmutable::createFromFormat('!Y-m-d', $data, $timezone);

    if ($dataInformada === false || $dataInformada->format('Y-m-d') !== $data) {
        return false;
    }

    $limitePassado = $hoje->modify('-30 days');

    return $dataInformada >= $limitePassado && $dataInformada <= $hoje;
}

function comprimentoTexto(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto) : strlen($texto);
}

$repo = new PresencaRepository();
$auditoria = new AuditoriaService();

$mensagem = '';
$erro = '';

if (isset($_GET['erro_pedidos']) && $_GET['erro_pedidos'] === '1') {
    $erro = 'Registre e salve a presença de todos os membros antes de acessar os pedidos de oração.';
}

$grupoId = (int) ($_GET['grupo_id'] ?? $_POST['grupo_id'] ?? 0);
$data = trim($_GET['data'] ?? $_POST['data'] ?? '');
$reuniaoId = 0;
$reuniao = null;
$listaPresencas = [];
$resumoGrupo = [];
$ultimasReunioes = [];
$presencasPendentes = false;

if ($grupoId > 0 && !Auth::isAdmin()) {
    if (!$repo->liderPodeAcessarGrupo(Auth::id(), $grupoId)) {
        http_response_code(403);
        die('Acesso negado a este Grupo Familiar.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_reuniao'])) {
    $grupoId = (int) ($_POST['grupo_id'] ?? 0);
    $data = trim($_POST['data'] ?? '');
    $horarioCriacao = trim($_POST['horario_criacao'] ?? '');

    try {
        if ($grupoId <= 0 || $data === '') {
            throw new InvalidArgumentException('Grupo Familiar e data são obrigatórios.');
        }

        if (!dataValida($data)) {
            throw new InvalidArgumentException('Data da reunião inválida.');
        }

        if (!dataDentroDaJanelaPermitida($data)) {
            throw new InvalidArgumentException('A reunião só pode ser criada para hoje ou até 30 dias atrás.');
        }

        if ($horarioCriacao === '') {
            throw new InvalidArgumentException('Informe o horário da reunião.');
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $horarioCriacao)) {
            throw new InvalidArgumentException('Horário da reunião inválido.');
        }

        if (!Auth::isAdmin() && !$repo->liderPodeAcessarGrupo(Auth::id(), $grupoId)) {
            http_response_code(403);
            die('Acesso negado a este Grupo Familiar.');
        }

        $reuniaoExistente = $repo->buscarReuniaoPorGrupoEData($grupoId, $data);
        $foiCriadaAgora = false;

        if ($reuniaoExistente !== null) {
            $reuniaoId = $reuniaoExistente;
            $mensagem = 'A reunião desta data já existia e foi carregada.';
        } else {
            $reuniaoId = $repo->criarReuniaoPorGrupoEData($grupoId, $data, $horarioCriacao);
            $mensagem = 'Reunião criada com sucesso.';
            $foiCriadaAgora = true;
        }

        $reuniao = $repo->buscarReuniao($reuniaoId);

        if ($foiCriadaAgora && $reuniao) {
            $auditoria->registrar(
                'criar',
                'reuniao',
                $reuniaoId,
                "Reunião criada para o GF {$reuniao['grupo_nome']} em " . date('d/m/Y', strtotime($data)) . " às {$horarioCriacao}.",
                null,
                $grupoId,
                $data
            );
        }

        if (isset($_GET['erro_pedidos']) && $_GET['erro_pedidos'] === '1') {
            $erro = 'Registre e salve a presença de todos os membros antes de acessar os pedidos de oração.';
        }


        $listaPresencas = $repo->listarPresencasPorReuniao($reuniaoId);
        $presencasPendentes = $repo->reuniaoTemPresencasPendentes($reuniaoId);
        $resumoGrupo = $repo->buscarResumoGrupo($grupoId);
        $ultimasReunioes = $repo->listarUltimasReunioesDoGrupo($grupoId, 5);
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_presencas'])) {
    $reuniaoId = (int) ($_POST['reuniao_id'] ?? 0);
    $grupoId = (int) ($_POST['grupo_id'] ?? 0);
    $data = trim($_POST['data'] ?? '');
    $local = trim($_POST['local'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $presencas = $_POST['presencas'] ?? [];

    $limiteObservacoes = 255;

    if (comprimentoTexto($observacoes) > $limiteObservacoes) {
        $erro = "O campo observações deve ter no máximo {$limiteObservacoes} caracteres.";
    } else {
        $totalPresencasDaReuniao = $repo->contarPresencasDaReuniao($reuniaoId);

        if (count($presencas) !== $totalPresencasDaReuniao) {
            $erro = 'Marque a presença ou ausência de todos os membros antes de salvar.';
        }
    }

    if ($erro === '') {
        try {
            if (!Auth::isAdmin() && !$repo->liderPodeAcessarGrupo(Auth::id(), $grupoId)) {
                http_response_code(403);
                die('Acesso negado a este Grupo Familiar.');
            }

            $repo->atualizarPresencasEReuniao($reuniaoId, $local, $observacoes, $presencas);
            $mensagem = 'Reunião e presenças atualizadas com sucesso.';

            $reuniao = $repo->buscarReuniao($reuniaoId);

            if ($reuniao) {
                $auditoria->registrar(
                    'atualizar',
                    'presencas',
                    $reuniaoId,
                    "Presenças atualizadas para a reunião do GF {$reuniao['grupo_nome']} em " . date('d/m/Y', strtotime($data)) . ".",
                    null,
                    $grupoId,
                    $data
                );
            }
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

if ($grupoId > 0 && $data !== '') {
    if (!dataValida($data)) {
        $erro = 'Data da reunião inválida.';
    } else {
        try {
            $reuniaoId = $repo->buscarReuniaoPorGrupoEData($grupoId, $data);

            if ($reuniaoId !== null) {
                $reuniao = $repo->buscarReuniao($reuniaoId);
                $listaPresencas = $repo->listarPresencasPorReuniao($reuniaoId);
            }

            $resumoGrupo = $repo->buscarResumoGrupo($grupoId);
            $ultimasReunioes = $repo->listarUltimasReunioesDoGrupo($grupoId, 5);
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
