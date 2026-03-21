<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/CartaRepository.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();
Auth::requireAdmin();

$repo      = new CartaRepository();
$auditoria = new AuditoriaService();
$mensagem  = '';
$erro      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataCarta      = trim($_POST['data_carta'] ?? '');
    $conteudo       = trim($_POST['conteudo'] ?? '');
    $pregacaoTitulo = trim($_POST['pregacao_titulo'] ?? '');
    $pregacaoLink   = trim($_POST['pregacao_link'] ?? '');
    $imagemUrl      = trim($_POST['imagem_url'] ?? '');
    $publicar       = isset($_POST['publicar']);

    // Monta JSON de avisos
    $avisosNomes    = $_POST['aviso_nome'] ?? [];
    $avisosDataEvt  = $_POST['aviso_data'] ?? [];
    $avisosConteudo = $_POST['aviso_conteudo'] ?? [];
    $avisosTipo     = $_POST['aviso_tipo'] ?? [];
    $avisos = [];
    foreach ($avisosNomes as $i => $nome) {
        if (trim($nome) === '') continue;
        $avisos[] = [
            'tipo'     => $avisosTipo[$i] ?? 'texto',
            'nome'     => $nome,
            'data'     => $avisosDataEvt[$i] ?? '',
            'conteudo' => $avisosConteudo[$i] ?? '',
        ];
    }
    $avisosJson = !empty($avisos) ? json_encode($avisos, JSON_UNESCAPED_UNICODE) : null;

    if ($dataCarta === '') {
        $erro = 'Informe a data da carta.';
    } else {
        try {
            $id = $repo->criar($dataCarta, $conteudo, $pregacaoTitulo, $pregacaoLink, $avisosJson, $imagemUrl, Auth::id());
            if ($publicar) $repo->publicar($id);

            $auditoria->registrar('criar', 'carta_semanal', $id,
                "Carta semanal criada para " . date('d/m/Y', strtotime($dataCarta)) . ($publicar ? ' e publicada.' : ' (rascunho).'),
                null, null, null);

            header('Location: /cartas.php?criada=1');
            exit;
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

$pageTitle = 'Nova Carta Semanal - JTRO';
require_once __DIR__ . '/../src/Views/cartas/criar.php';