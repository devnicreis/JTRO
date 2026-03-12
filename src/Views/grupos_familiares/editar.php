<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxLocalFixo = document.getElementById('local_fixo');
        const inputLocalPadrao = document.getElementById('local_padrao');

        function atualizarObrigatoriedadeLocalPadrao() {
            inputLocalPadrao.required = checkboxLocalFixo.checked;
        }

        atualizarObrigatoriedadeLocalPadrao();
        checkboxLocalFixo.addEventListener('change', atualizarObrigatoriedadeLocalPadrao);
    });
</script>

<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/grupos_familiares.php">← Voltar para Grupos Familiares</a>
</div>

<h1>Editar Grupo Familiar</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/grupos_familiares_editar.php">
    <input type="hidden" name="id" value="<?php echo $grupoId; ?>">

    <div class="campo">
        <label for="nome">Nome do Grupo Familiar</label>
        <input
            type="text"
            id="nome"
            name="nome"
            required
            value="<?php echo htmlspecialchars($grupo['nome']); ?>">
    </div>

    <div class="grid">
        <div class="campo">
            <label for="dia_semana">Dia da semana</label>
            <select id="dia_semana" name="dia_semana" required>
                <option value="">Selecione</option>
                <?php foreach ($dias as $dia): ?>
                    <option value="<?php echo $dia; ?>" <?php echo $grupo['dia_semana'] === $dia ? 'selected' : ''; ?>>
                        <?php echo ucfirst($dia); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="horario">Horário</label>
            <input
                type="time"
                id="horario"
                name="horario"
                required
                value="<?php echo htmlspecialchars($grupo['horario']); ?>">
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="local_padrao">Local padrão</label>
            <input
                type="text"
                id="local_padrao"
                name="local_padrao"
                value="<?php echo htmlspecialchars($grupo['local_padrao'] ?? ''); ?>">
        </div>

        <div class="campo">
            <div class="campo-checkbox">
                <input
                    type="checkbox"
                    id="local_fixo"
                    name="local_fixo"
                    value="1"
                    <?php echo (int)($grupo['local_fixo'] ?? 0) === 1 ? 'checked' : ''; ?>>
                <label for="local_fixo">Este GF possui local fixo</label>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label>Líderes</label>
            <div class="checkbox-lista">
                <?php foreach ($pessoas as $pessoa): ?>
                    <div class="checkbox-item">
                        <input
                            type="checkbox"
                            id="lider_<?php echo $pessoa['id']; ?>"
                            name="lideres[]"
                            value="<?php echo $pessoa['id']; ?>"
                            <?php echo in_array((int)$pessoa['id'], $lideresSelecionados, true) ? 'checked' : ''; ?>>
                        <label for="lider_<?php echo $pessoa['id']; ?>">
                            <?php echo htmlspecialchars($pessoa['nome']); ?> (<?php echo htmlspecialchars($pessoa['cargo']); ?>)
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="campo">
            <label>Membros</label>
            <div class="checkbox-lista">
                <?php foreach ($pessoas as $pessoa): ?>
                    <div class="checkbox-item">
                        <input
                            type="checkbox"
                            id="membro_<?php echo $pessoa['id']; ?>"
                            name="membros[]"
                            value="<?php echo $pessoa['id']; ?>"
                            <?php echo in_array((int)$pessoa['id'], $membrosSelecionados, true) ? 'checked' : ''; ?>>
                        <label for="membro_<?php echo $pessoa['id']; ?>">
                            <?php echo htmlspecialchars($pessoa['nome']); ?> (<?php echo htmlspecialchars($pessoa['cargo']); ?>)
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <button type="submit">Salvar alterações</button>
</form>

<?php require __DIR__ . '/../layouts/footer.php'; ?>