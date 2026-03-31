<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/CantinaRepository.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();
Auth::requireAdmin();

$cantinaRepo = new CantinaRepository();
$grupoRepo = new GrupoFamiliarRepository();

$mensagem = '';
$erro = '';

$ano = (int) ($_GET['ano'] ?? $_POST['ano'] ?? date('Y'));
if ($ano < 2000 || $ano > 2100) {
    $ano = (int) date('Y');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $redirectAno = (int) ($_POST['ano'] ?? $ano);

    try {
        if ($acao === 'salvar') {
            $id = (int) ($_POST['id'] ?? 0);
            $dataEscala = trim($_POST['data_escala'] ?? '');
            $grupoFamiliarId = (int) ($_POST['grupo_familiar_id'] ?? 0);
            $observacoes = trim($_POST['observacoes'] ?? '');

            $cantinaRepo->salvarEscala($id > 0 ? $id : null, $grupoFamiliarId, $dataEscala, $observacoes);
            header('Location: /cantina.php?ano=' . $redirectAno . '&mensagem=' . urlencode('Escala da cantina salva com sucesso.'));
            exit;
        }

        if ($acao === 'excluir') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $cantinaRepo->excluirEscala($id);
            }
            header('Location: /cantina.php?ano=' . $redirectAno . '&mensagem=' . urlencode('Escala da cantina removida com sucesso.'));
            exit;
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

if (isset($_GET['mensagem']) && trim((string) $_GET['mensagem']) !== '') {
    $mensagem = trim((string) $_GET['mensagem']);
}

$filtros = [
    'data_escala' => trim($_GET['data_escala'] ?? ''),
    'grupo_familiar_id' => trim($_GET['grupo_familiar_id'] ?? ''),
];

$gruposFamiliares = $grupoRepo->listarAtivos();
$escalas = $cantinaRepo->listarEscalasDoAno($ano, $filtros);
$pageTitle = 'Cantina - JTRO';

require_once __DIR__ . '/../src/Views/cantina/index_v2.php';
