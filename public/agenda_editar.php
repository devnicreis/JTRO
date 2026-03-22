<?php
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/AgendaRepository.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();
Auth::requireAdmin();

$repo   = new AgendaRepository();
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$evento = $id > 0 ? $repo->buscarPorId($id) : null;

if (!$evento) { header('Location: /agenda.php'); exit; }

$erro        = '';
$mensagem    = '';
$departamentos = AgendaRepository::DEPARTAMENTOS;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo       = trim($_POST['titulo']       ?? '');
    $data         = trim($_POST['data']         ?? '');
    $horario      = trim($_POST['hora_inicio']  ?? $_POST['horario'] ?? '');
    $horarioFim   = trim($_POST['hora_fim']     ?? $_POST['horario_fim'] ?? '') ?: null;
    $departamento = trim($_POST['departamento'] ?? '');
    $descricao    = trim($_POST['descricao']    ?? '') ?: null;

    if ($titulo === '' || $data === '' || $horario === '' || $departamento === '') {
        $erro = 'Preencha título, data, horário e departamento.';
    } else {
        try {
            $repo->atualizar($id, $titulo, $data, $horario, $horarioFim, $departamento, $descricao);
            header('Location: /agenda.php?editado=1&dia=' . urlencode($data));
            exit;
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
    $evento = $repo->buscarPorId($id);
}

$pageTitle = 'Editar Evento - JTRO';
require_once __DIR__ . '/../src/Views/agenda/editar.php';