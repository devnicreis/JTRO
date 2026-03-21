<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/CartaRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$repo = new CartaRepository();
$isAdmin = Auth::isAdmin();
$mensagem = '';
$erro = '';

// Ações POST (admin apenas)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $acao = $_POST['acao'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($acao === 'publicar' && $id > 0) {
        $repo->publicar($id);
        $mensagem = 'Carta publicada com sucesso.';
    } elseif ($acao === 'despublicar' && $id > 0) {
        $repo->despublicar($id);
        $mensagem = 'Carta despublicada.';
    } elseif ($acao === 'excluir' && $id > 0) {
        $repo->excluir($id);
        $mensagem = 'Carta excluída.';
    }
}

$cartas = $isAdmin ? $repo->listarTodas() : $repo->listarPublicadas();
$pageTitle = 'Carta Semanal - JTRO';

require_once __DIR__ . '/../src/Views/cartas/index.php';