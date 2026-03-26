<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
$perfisGrupo = opcoesPerfilGrupo();
$domingos = [1 => '1º Domingo', 2 => '2º Domingo', 3 => '3º Domingo', 4 => '4º Domingo', 5 => '5º Domingo'];
?>

<div class="page-header">
    <h1>Editar Grupo Familiar</h1>
</div>

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
        <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($grupo['nome']); ?>">
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
            <input type="time" id="horario" name="horario" required value="<?php echo htmlspecialchars($grupo['horario']); ?>">
        </div>
    </div>

    <div class="campo">
        <label for="perfil_grupo">Perfil do grupo</label>
        <select id="perfil_grupo" name="perfil_grupo" required>
            <?php foreach ($perfisGrupo as $valor => $label): ?>
                <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($grupo['perfil_grupo'] ?? '') === $valor) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="local_padrao">Local padrão</label>
            <input type="text" id="local_padrao" name="local_padrao" value="<?php echo htmlspecialchars($grupo['local_padrao'] ?? ''); ?>">
        </div>
        <div class="campo" style="display:flex; align-items:flex-end; padding-bottom:2px;">
            <div class="checkbox-item">
                <input type="checkbox" id="local_fixo" name="local_fixo" value="1" <?php echo (int) $grupo['local_fixo'] === 1 ? 'checked' : ''; ?>>
                <label for="local_fixo">Este GF possui local fixo</label>
            </div>
        </div>
    </div>

    <div class="escala-secao">
        <div class="escala-secao-titulo">Escalas do GF</div>
        <div class="grid">
            <div class="campo">
                <label for="item_celeiro">
                    Item do Projeto Celeiro
                    <span class="escala-hint">ex: Arroz, Feijão, Macarrão</span>
                </label>
                <input type="text" id="item_celeiro" name="item_celeiro" placeholder="Ex: Arroz"
                       value="<?php echo htmlspecialchars($grupo['item_celeiro'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="domingo_oracao_culto">Domingo de Oração antes do Culto</label>
                <select id="domingo_oracao_culto" name="domingo_oracao_culto">
                    <option value="0" <?php echo empty($grupo['domingo_oracao_culto']) ? 'selected' : ''; ?>>Não escalonado</option>
                    <?php $domAtual = (int) ($grupo['domingo_oracao_culto'] ?? 0); ?>
                    <?php foreach ($domingos as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo $domAtual === $val ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label>Líderes</label>
            <input type="text" class="gf-busca" placeholder="Buscar líder..." oninput="filtrarCheckbox(this, 'lista-lideres')" style="margin-bottom:8px;">
            <div class="checkbox-lista gf-lista-scroll" id="lista-lideres">
                <?php foreach ($pessoas as $pessoa): ?>
                    <div class="checkbox-item gf-item">
                        <input type="checkbox" id="lider_<?php echo $pessoa['id']; ?>"
                               name="lideres[]" value="<?php echo $pessoa['id']; ?>"
                               <?php echo in_array((int) $pessoa['id'], $lideresSelecionados, true) ? 'checked' : ''; ?>>
                        <label for="lider_<?php echo $pessoa['id']; ?>">
                            <?php echo htmlspecialchars($pessoa['nome']); ?>
                            <span class="gf-cargo">(<?php echo htmlspecialchars($pessoa['cargo']); ?>)</span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="campo">
            <label>Membros</label>
            <input type="text" class="gf-busca" placeholder="Buscar membro..." oninput="filtrarCheckbox(this, 'lista-membros')" style="margin-bottom:8px;">
            <div class="checkbox-lista gf-lista-scroll" id="lista-membros">
                <?php foreach ($pessoas as $pessoa): ?>
                    <div class="checkbox-item gf-item">
                        <input type="checkbox" id="membro_<?php echo $pessoa['id']; ?>"
                               name="membros[]" value="<?php echo $pessoa['id']; ?>"
                               <?php echo in_array((int) $pessoa['id'], $membrosSelecionados, true) ? 'checked' : ''; ?>>
                        <label for="membro_<?php echo $pessoa['id']; ?>">
                            <?php echo htmlspecialchars($pessoa['nome']); ?>
                            <span class="gf-cargo">(<?php echo htmlspecialchars($pessoa['cargo']); ?>)</span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <button type="submit">Salvar alterações</button>
</form>

<script>
function filtrarCheckbox(input, listaId) {
    const t = input.value.toLowerCase().trim();
    document.querySelectorAll('#' + listaId + ' .gf-item').forEach(function(item) {
        item.style.display = (!t || item.querySelector('label').textContent.toLowerCase().includes(t)) ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const fixo = document.getElementById('local_fixo');
    const local = document.getElementById('local_padrao');
    if (fixo && local) {
        function sync() { local.required = fixo.checked; }
        sync();
        fixo.addEventListener('change', sync);
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
