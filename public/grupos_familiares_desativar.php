<?php

require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

$repo = new GrupoFamiliarRepository();
$erro = '';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: /grupos_familiares.php');
    exit;
}

$grupo = $repo->buscarPorId($id);

if (!$grupo) {
    header('Location: /grupos_familiares.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = trim($_POST['motivo'] ?? '');

    if ($motivo === '') {
        $erro = 'Informe o motivo da desativação.';
    } elseif (mb_strlen($motivo) > 250) {
        $erro = 'O motivo deve ter no máximo 250 caracteres.';
    } else {
        $repo->desativar($id, $motivo);
        header('Location: /grupos_familiares.php');
        exit;
    }
}
?>
<?php require_once __DIR__ . '/../src/Views/layouts/header.php'; ?>

<div class="menu">
    <a href="/grupos_familiares.php">← Voltar para Grupos Familiares</a>
</div>

<h1>Desativar Grupo Familiar</h1>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/grupos_familiares_desativar.php">
    <input type="hidden" name="id" value="<?php echo (int) $grupo['id']; ?>">

    <div class="campo">
        <label>Grupo Familiar</label>
        <input type="text" value="<?php echo htmlspecialchars($grupo['nome']); ?>" readonly>
    </div>

    <div class="campo">
        <label for="motivo">Informe o motivo da desativação</label>
        <textarea id="motivo" name="motivo" maxlength="250" required><?php echo htmlspecialchars($_POST['motivo'] ?? ''); ?></textarea>
    </div>

    <div class="acoes" style="justify-content:flex-start;">
        <button type="submit">Confirmar desativação</button>
        <a class="botao-link botao-secundario" href="/grupos_familiares.php">Cancelar</a>
    </div>
</form>

<?php require_once __DIR__ . '/../src/Views/layouts/footer.php'; ?>
