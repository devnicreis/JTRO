<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/AgendaRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();
Auth::requireAdmin();

$repo          = new AgendaRepository();
$erro          = '';
$departamentos = AgendaRepository::DEPARTAMENTOS;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo       = trim($_POST['titulo']       ?? '');
    $data         = trim($_POST['data']         ?? '');
    $horario      = trim($_POST['hora_inicio']  ?? '');
    $horarioFim   = trim($_POST['hora_fim']     ?? '') ?: null;
    $departamento = trim($_POST['departamento'] ?? '');
    $descricao    = trim($_POST['descricao']    ?? '') ?: null;

    if ($titulo === '' || $data === '' || $horario === '' || $departamento === '') {
        $erro = 'Preencha título, data, horário e departamento.';
    } else {
        try {
            $repo->criar($titulo, $data, $horario, $horarioFim, $departamento, $descricao, Auth::id());
            header('Location: /agenda.php?criado=1&dia=' . urlencode($data));
            exit;
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

$pageTitle = 'Novo Evento - JTRO';
require_once __DIR__ . '/../src/Views/agenda/criar.php';