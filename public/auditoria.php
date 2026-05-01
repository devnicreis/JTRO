<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

date_default_timezone_set('America/Sao_Paulo');

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

$pessoaRepo = new PessoaRepository();
$grupoRepo  = new GrupoFamiliarRepository();
$auditoria  = new AuditoriaService();

$usuarioId           = (int) ($_GET['usuario_id'] ?? 0);
$grupoFamiliarId     = (int) ($_GET['grupo_familiar_id'] ?? 0);
$dataAlteracaoInicio = trim($_GET['data_alteracao_inicio'] ?? '');
$dataAlteracaoFim    = trim($_GET['data_alteracao_fim'] ?? '');
$dataReuniao         = trim($_GET['data_reuniao'] ?? '');

$limitesPermitidos = [20, 50, 100];
$limite = (int) ($_GET['limite'] ?? 20);
if (!in_array($limite, $limitesPermitidos, true)) {
    $limite = 20;
}

$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$usuarios = $pessoaRepo->listarTodos();
$grupos   = $grupoRepo->listarTodos();

$filtroUsuarioId = $usuarioId > 0 ? $usuarioId : null;
$filtroGrupoId = $grupoFamiliarId > 0 ? $grupoFamiliarId : null;
$filtroDataInicio = $dataAlteracaoInicio !== '' ? $dataAlteracaoInicio : null;
$filtroDataFim = $dataAlteracaoFim !== '' ? $dataAlteracaoFim : null;
$filtroDataReuniao = $dataReuniao !== '' ? $dataReuniao : null;

$totalLogs = $auditoria->contarLogsFiltrados(
    $filtroUsuarioId,
    $filtroGrupoId,
    $filtroDataInicio,
    $filtroDataFim,
    $filtroDataReuniao
);
$totalPaginas = max(1, (int) ceil($totalLogs / $limite));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $limite;

$logs = $auditoria->listarLogsFiltrados(
    $filtroUsuarioId,
    $filtroGrupoId,
    $filtroDataInicio,
    $filtroDataFim,
    $filtroDataReuniao,
    $limite,
    $offset
);

$pageTitle = 'Auditoria - JTRO';

require_once __DIR__ . '/../src/Views/auditoria/index.php';
