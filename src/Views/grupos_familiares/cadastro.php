<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
$perfisGrupo = opcoesPerfilGrupo();
$dias = ['segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
$domingosFiltro = [1 => '1º Domingo', 2 => '2º Domingo', 3 => '3º Domingo', 4 => '4º Domingo', 5 => '5º Domingo'];
?>

<div class="page-header">
    <h1>Cadastro de Grupos Familiares</h1>
    <p class="page-header-subtitulo">Crie a estrutura do grupo, defina escalas e vincule líderes e membros sem misturar isso com a listagem geral.</p>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>
<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/grupos_familiares.php">
    <div class="campo">
        <label for="nome">Nome do Grupo Familiar</label>
        <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
    </div>

    <div class="grid">
        <div class="campo">
            <label for="dia_semana">Dia da semana</label>
            <select id="dia_semana" name="dia_semana" required>
                <option value="">Selecione</option>
                <?php $diaSelecionado = $_POST['dia_semana'] ?? ''; ?>
                <?php foreach ($dias as $dia): ?>
                    <option value="<?php echo htmlspecialchars($dia); ?>" <?php echo $diaSelecionado === $dia ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($dia)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="horario">Horário</label>
            <input type="time" id="horario" name="horario" required value="<?php echo htmlspecialchars($_POST['horario'] ?? ''); ?>">
        </div>
    </div>

    <div class="campo">
        <label for="perfil_grupo">Perfil do grupo</label>
        <select id="perfil_grupo" name="perfil_grupo" required>
            <option value="">Selecione</option>
            <?php foreach ($perfisGrupo as $valor => $label): ?>
                <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($_POST['perfil_grupo'] ?? '') === $valor) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="local_padrao">Local padrão</label>
            <input type="text" id="local_padrao" name="local_padrao" value="<?php echo htmlspecialchars($_POST['local_padrao'] ?? ''); ?>">
        </div>
        <div class="campo" style="display:flex; align-items:flex-end; padding-bottom:2px;">
            <div class="checkbox-item">
                <input type="checkbox" id="local_fixo" name="local_fixo" value="1" <?php echo isset($_POST['local_fixo']) ? 'checked' : ''; ?>>
                <label for="local_fixo">Este GF possui local fixo</label>
            </div>
        </div>
    </div>

    <div class="escala-secao">
        <div class="escala-secao-titulo">Escalas do GF</div>
        <div class="grid">
            <div class="campo">
                <label for="item_celeiro">Item do Projeto Celeiro</label>
                <input type="text" id="item_celeiro" name="item_celeiro" placeholder="Ex.: Arroz" value="<?php echo htmlspecialchars($_POST['item_celeiro'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="domingo_oracao_culto">Domingo de oração antes do culto</label>
                <select id="domingo_oracao_culto" name="domingo_oracao_culto">
                    <option value="0">Não escalonado</option>
                    <?php $domingoSelecionado = (int) ($_POST['domingo_oracao_culto'] ?? 0); ?>
                    <?php foreach ($domingosFiltro as $valor => $label): ?>
                        <option value="<?php echo $valor; ?>" <?php echo $domingoSelecionado === $valor ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
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
                    <div class="checkbox-item gf-item" data-genero="<?php echo htmlspecialchars($pessoa['genero'] ?? ''); ?>">
                        <input type="checkbox" id="lider_<?php echo (int) $pessoa['id']; ?>" name="lideres[]" value="<?php echo (int) $pessoa['id']; ?>" <?php echo in_array((string) $pessoa['id'], $_POST['lideres'] ?? [], true) ? 'checked' : ''; ?>>
                        <label for="lider_<?php echo (int) $pessoa['id']; ?>">
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
                    <div class="checkbox-item gf-item" data-genero="<?php echo htmlspecialchars($pessoa['genero'] ?? ''); ?>">
                        <input type="checkbox" id="membro_<?php echo (int) $pessoa['id']; ?>" name="membros[]" value="<?php echo (int) $pessoa['id']; ?>" <?php echo in_array((string) $pessoa['id'], $_POST['membros'] ?? [], true) ? 'checked' : ''; ?>>
                        <label for="membro_<?php echo (int) $pessoa['id']; ?>">
                            <?php echo htmlspecialchars($pessoa['nome']); ?>
                            <span class="gf-cargo">(<?php echo htmlspecialchars($pessoa['cargo']); ?>)</span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="acoes">
        <button type="submit">Cadastrar Grupo Familiar</button>
        <a href="/grupos_familiares_cadastrados.php" class="botao-link botao-secundario">Ver GFs cadastrados</a>
    </div>
</form>

<script>
function filtrarCheckbox(input, listaId) {
    const termo = input.value.toLowerCase().trim();
    document.querySelectorAll('#' + listaId + ' .gf-item').forEach(function(item) {
        const checkbox = item.querySelector('input[type="checkbox"]');
        const bloqueado = checkbox && checkbox.disabled;
        const corresponde = !termo || item.querySelector('label').textContent.toLowerCase().includes(termo);
        item.style.display = (!bloqueado && corresponde) ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const fixo = document.getElementById('local_fixo');
    const local = document.getElementById('local_padrao');
    const perfilGrupo = document.getElementById('perfil_grupo');

    if (fixo && local) {
        function sincronizarLocal() {
            local.required = fixo.checked;
        }

        sincronizarLocal();
        fixo.addEventListener('change', sincronizarLocal);
    }

    if (perfilGrupo) {
        function sincronizarParticipantesPorPerfil() {
            const somenteMulheres = perfilGrupo.value === 'mulheres';
            document.querySelectorAll('.gf-item[data-genero]').forEach(function(item) {
                const genero = item.getAttribute('data-genero') || '';
                const checkbox = item.querySelector('input[type="checkbox"]');
                const permitido = !somenteMulheres || genero === 'feminino';

                item.style.display = permitido ? '' : 'none';
                if (checkbox) {
                    checkbox.disabled = !permitido;
                    if (!permitido) {
                        checkbox.checked = false;
                    }
                }
            });
        }

        sincronizarParticipantesPorPerfil();
        perfilGrupo.addEventListener('change', sincronizarParticipantesPorPerfil);
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
