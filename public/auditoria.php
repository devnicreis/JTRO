<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

date_default_timezone_set('America/Sao_Paulo');

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

$pessoaRepo = new PessoaRepository();
$grupoRepo = new GrupoFamiliarRepository();
$auditoria = new AuditoriaService();

$usuarioId = (int) ($_GET['usuario_id'] ?? 0);
$grupoFamiliarId = (int) ($_GET['grupo_familiar_id'] ?? 0);
$dataAlteracaoInicio = trim($_GET['data_alteracao_inicio'] ?? '');
$dataAlteracaoFim = trim($_GET['data_alteracao_fim'] ?? '');
$dataReuniao = trim($_GET['data_reuniao'] ?? '');

$usuarios = $pessoaRepo->listarTodos();
$grupos = $grupoRepo->listarTodos();

$logs = $auditoria->listarLogsFiltrados(
    $usuarioId > 0 ? $usuarioId : null,
    $grupoFamiliarId > 0 ? $grupoFamiliarId : null,
    $dataAlteracaoInicio !== '' ? $dataAlteracaoInicio : null,
    $dataAlteracaoFim !== '' ? $dataAlteracaoFim : null,
    $dataReuniao !== '' ? $dataReuniao : null,
    200
);

$pageTitle = 'Auditoria - JTRO';

require_once __DIR__ . '/../src/Views/auditoria/index.php';