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

// Limite variável: 20, 50 ou 100
$limitesPermitidos = [20, 50, 100];
$limite = (int) ($_GET['limite'] ?? 20);
if (!in_array($limite, $limitesPermitidos)) $limite = 20;

$usuarios = $pessoaRepo->listarTodos();
$grupos   = $grupoRepo->listarTodos();

$logs = $auditoria->listarLogsFiltrados(
    $usuarioId > 0       ? $usuarioId       : null,
    $grupoFamiliarId > 0 ? $grupoFamiliarId : null,
    $dataAlteracaoInicio !== '' ? $dataAlteracaoInicio : null,
    $dataAlteracaoFim    !== '' ? $dataAlteracaoFim    : null,
    $dataReuniao         !== '' ? $dataReuniao         : null,
    $limite
);

$pageTitle = 'Auditoria - JTRO';

require_once __DIR__ . '/../src/Views/auditoria/index.php';