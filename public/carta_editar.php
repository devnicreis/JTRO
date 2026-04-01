<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/CartaRepository.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';
require_once __DIR__ . '/../src/Services/CartaContentSanitizer.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();
Auth::requireAdmin();

$repo      = new CartaRepository();
$auditoria = new AuditoriaService();
$mensagem  = '';
$erro      = '';

$id    = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$carta = $id > 0 ? $repo->buscarPorId($id) : null;

if (!$carta) {
    header('Location: /cartas.php');
    exit;
}

$carta['conteudo'] = CartaContentSanitizer::sanitizeHtml($carta['conteudo'] ?? '');
$carta['pregacao_link'] = CartaContentSanitizer::sanitizeExternalUrl($carta['pregacao_link'] ?? '') ?? '';
$carta['imagem_url'] = CartaContentSanitizer::sanitizeExternalUrl($carta['imagem_url'] ?? '') ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataCarta      = trim($_POST['data_carta'] ?? '');
    $conteudo       = CartaContentSanitizer::sanitizeHtml($_POST['conteudo'] ?? '');
    $pregacaoTitulo = trim($_POST['pregacao_titulo'] ?? '');
    $pregacaoLink   = CartaContentSanitizer::sanitizeExternalUrl($_POST['pregacao_link'] ?? '');
    $imagemUrl      = CartaContentSanitizer::sanitizeExternalUrl($_POST['imagem_url'] ?? '');
    $publicar       = isset($_POST['publicar']);

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
    } elseif (trim((string) ($_POST['pregacao_link'] ?? '')) !== '' && $pregacaoLink === null) {
        $erro = 'Informe um link valido para a pregacao com http:// ou https://.';
    } elseif (trim((string) ($_POST['imagem_url'] ?? '')) !== '' && $imagemUrl === null) {
        $erro = 'Informe uma URL de imagem valida com http:// ou https://.';
    } else {
        try {
            $repo->atualizar($id, $dataCarta, $conteudo, $pregacaoTitulo, $pregacaoLink, $avisosJson, $imagemUrl);
            if ($publicar && !$carta['publicada']) $repo->publicar($id);

            $auditoria->registrar('atualizar', 'carta_semanal', $id,
                "Carta semanal de " . date('d/m/Y', strtotime($dataCarta)) . " atualizada.",
                null, null, null);

            header('Location: /cartas.php?editada=1');
            exit;
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
    // Recarrega após erro
    $carta = $repo->buscarPorId($id);
}

$pageTitle = 'Editar Carta Semanal - JTRO';
require_once __DIR__ . '/../src/Views/cartas/editar.php';
