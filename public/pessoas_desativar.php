<?php

require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

$repo = new PessoaRepository();
$erro = '';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: /pessoas.php');
    exit;
}

$pessoa = $repo->buscarPorId($id);

if (!$pessoa) {
    header('Location: /pessoas.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['motivo_tipo'] ?? '';
    $detalhe = trim($_POST['motivo_detalhe'] ?? '');
    $texto = trim($_POST['motivo_texto'] ?? '');

    $motivosValidos = ['mudanca_igreja', 'transferencia_abba', 'nao_sabe', 'nao_frequenta', 'motivos_pessoais'];

    if (!in_array($tipo, $motivosValidos, true)) {
        $erro = 'Selecione um motivo de desativação.';
    } elseif (in_array($tipo, ['mudanca_igreja', 'transferencia_abba'], true) && $detalhe === '') {
        $erro = 'Informe o destino relacionado ao motivo selecionado.';
    } elseif (in_array($tipo, ['mudanca_igreja', 'transferencia_abba'], true) && mb_strlen($detalhe) > 100) {
        $erro = 'O destino deve ter no máximo 100 caracteres.';
    } elseif ($tipo === 'motivos_pessoais' && $texto === '') {
        $erro = 'Resuma os motivos pessoais da desativação.';
    } elseif ($tipo === 'motivos_pessoais' && mb_strlen($texto) > 250) {
        $erro = 'O resumo dos motivos pessoais deve ter no máximo 250 caracteres.';
    } else {
        $repo->desativar($id, [
            'tipo' => $tipo,
            'detalhe' => $detalhe,
            'texto' => $texto,
        ]);

        header('Location: /pessoas.php');
        exit;
    }
}
?>
<?php require_once __DIR__ . '/../src/Views/layouts/header.php'; ?>

<div class="menu">
    <a href="/pessoas.php">← Voltar para Pessoas</a>
</div>

<h1>Desativar Pessoa</h1>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/pessoas_desativar.php">
    <input type="hidden" name="id" value="<?php echo (int) $pessoa['id']; ?>">

    <div class="campo">
        <label>Pessoa</label>
        <input type="text" value="<?php echo htmlspecialchars($pessoa['nome']); ?>" readonly>
    </div>

    <div class="campo">
        <label for="motivo_tipo">Informe o motivo da desativação</label>
        <select id="motivo_tipo" name="motivo_tipo" required>
            <option value="">Selecione</option>
            <option value="mudanca_igreja" <?php echo (($_POST['motivo_tipo'] ?? '') === 'mudanca_igreja') ? 'selected' : ''; ?>>Mudança de Igreja</option>
            <option value="transferencia_abba" <?php echo (($_POST['motivo_tipo'] ?? '') === 'transferencia_abba') ? 'selected' : ''; ?>>Transferência para outra C.C. Abba</option>
            <option value="nao_sabe" <?php echo (($_POST['motivo_tipo'] ?? '') === 'nao_sabe') ? 'selected' : ''; ?>>Não sabe para qual Igreja vai</option>
            <option value="nao_frequenta" <?php echo (($_POST['motivo_tipo'] ?? '') === 'nao_frequenta') ? 'selected' : ''; ?>>Decidiu não frequentar mais igreja evangélica</option>
            <option value="motivos_pessoais" <?php echo (($_POST['motivo_tipo'] ?? '') === 'motivos_pessoais') ? 'selected' : ''; ?>>Motivos pessoais</option>
        </select>
    </div>

    <div class="campo" id="campo_motivo_detalhe">
        <label for="motivo_detalhe">Para qual?</label>
        <input type="text" id="motivo_detalhe" name="motivo_detalhe" maxlength="100"
               value="<?php echo htmlspecialchars($_POST['motivo_detalhe'] ?? ''); ?>">
    </div>

    <div class="campo" id="campo_motivo_texto">
        <label for="motivo_texto">Resuma</label>
        <textarea id="motivo_texto" name="motivo_texto" maxlength="250"><?php echo htmlspecialchars($_POST['motivo_texto'] ?? ''); ?></textarea>
    </div>

    <div class="acoes" style="justify-content:flex-start;">
        <button type="submit">Confirmar desativação</button>
        <a class="botao-link botao-secundario" href="/pessoas.php">Cancelar</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campoTipo = document.getElementById('motivo_tipo');
    const campoDetalhe = document.getElementById('campo_motivo_detalhe');
    const campoTexto = document.getElementById('campo_motivo_texto');
    const inputDetalhe = document.getElementById('motivo_detalhe');
    const inputTexto = document.getElementById('motivo_texto');

    function atualizarMotivos() {
        const valor = campoTipo ? campoTipo.value : '';
        const precisaDetalhe = ['mudanca_igreja', 'transferencia_abba'].includes(valor);
        const precisaTexto = valor === 'motivos_pessoais';

        if (campoDetalhe) campoDetalhe.style.display = precisaDetalhe ? '' : 'none';
        if (campoTexto) campoTexto.style.display = precisaTexto ? '' : 'none';
        if (inputDetalhe) inputDetalhe.required = precisaDetalhe;
        if (inputTexto) inputTexto.required = precisaTexto;

        if (!precisaDetalhe && inputDetalhe) inputDetalhe.value = '';
        if (!precisaTexto && inputTexto) inputTexto.value = '';
    }

    atualizarMotivos();
    if (campoTipo) campoTipo.addEventListener('change', atualizarMotivos);
});
</script>

<?php require_once __DIR__ . '/../src/Views/layouts/footer.php'; ?>
