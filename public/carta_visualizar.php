<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/CartaRepository.php';
require_once __DIR__ . '/../src/Repositories/AvisoRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$repo      = new CartaRepository();
$avisoRepo = new AvisoRepository();

$id    = (int) ($_GET['id'] ?? 0);
$carta = $id > 0 ? $repo->buscarPorId($id) : null;

if (!$carta || (!Auth::isAdmin() && !$carta['publicada'])) {
    header('Location: /cartas.php');
    exit;
}

// Marca como lida automaticamente
$chave = 'carta_nova_' . $id;
$avisoRepo->marcarComoLido(Auth::id(), $chave);

$pageTitle = 'Carta Semanal - JTRO';
require_once __DIR__ . '/../src/Views/cartas/visualizar.php';