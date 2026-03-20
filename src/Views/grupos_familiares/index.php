<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <h1>Cadastro de Grupos Familiares</h1>
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
        <input type="text" id="nome" name="nome" required
               value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
    </div>

    <div class="grid">
        <div class="campo">
            <label for="dia_semana">Dia da semana</label>
            <select id="dia_semana" name="dia_semana" required>
                <option value="">Selecione</option>
                <?php
                $dias = ['segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado','domingo'];
                $diaSel = $_POST['dia_semana'] ?? '';
                foreach ($dias as $dia):
                ?>
                    <option value="<?php echo $dia; ?>" <?php echo $diaSel === $dia ? 'selected' : ''; ?>>
                        <?php echo ucfirst($dia); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="horario">Horário</label>
            <input type="time" id="horario" name="horario" required
                   value="<?php echo htmlspecialchars($_POST['horario'] ?? ''); ?>">
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="local_padrao">Local padrão</label>
            <input type="text" id="local_padrao" name="local_padrao"
                   value="<?php echo htmlspecialchars($_POST['local_padrao'] ?? ''); ?>">
        </div>
        <div class="campo" style="display:flex; align-items:flex-end; padding-bottom:2px;">
            <div class="checkbox-item">
                <input type="checkbox" id="local_fixo" name="local_fixo" value="1"
                       <?php echo isset($_POST['local_fixo']) ? 'checked' : ''; ?>>
                <label for="local_fixo">Este GF possui local fixo</label>
            </div>
        </div>
    </div>

    <!-- Escalas -->
    <div class="escala-secao">
        <div class="escala-secao-titulo">Escalas do GF</div>
        <div class="grid">
            <div class="campo">
                <label for="item_celeiro">
                    Item do Projeto Celeiro
                    <span class="escala-hint">ex: Arroz, Feijão, Macarrão</span>
                </label>
                <input type="text" id="item_celeiro" name="item_celeiro"
                       placeholder="Ex: Arroz"
                       value="<?php echo htmlspecialchars($_POST['item_celeiro'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="domingo_oracao_culto">Domingo de Oração antes do Culto</label>
                <select id="domingo_oracao_culto" name="domingo_oracao_culto">
                    <option value="0">Não escalonado</option>
                    <?php
                    $domSel = (int)($_POST['domingo_oracao_culto'] ?? 0);
                    $domingos = [1=>'1º Domingo',2=>'2º Domingo',3=>'3º Domingo',4=>'4º Domingo',5=>'5º Domingo'];
                    foreach ($domingos as $val => $label):
                    ?>
                        <option value="<?php echo $val; ?>" <?php echo $domSel === $val ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Líderes e Membros -->
    <div class="grid">
        <div class="campo">
            <label>Líderes</label>
            <input type="text" class="gf-busca" placeholder="Buscar líder..."
                   oninput="filtrarCheckbox(this, 'lista-lideres')" style="margin-bottom:8px;">
            <div class="checkbox-lista gf-lista-scroll" id="lista-lideres">
                <?php foreach ($pessoas as $pessoa): ?>
                    <div class="checkbox-item gf-item">
                        <input type="checkbox" id="lider_<?php echo $pessoa['id']; ?>"
                               name="lideres[]" value="<?php echo $pessoa['id']; ?>"
                               <?php echo in_array((string)$pessoa['id'], $_POST['lideres'] ?? [], true) ? 'checked' : ''; ?>>
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
            <input type="text" class="gf-busca" placeholder="Buscar membro..."
                   oninput="filtrarCheckbox(this, 'lista-membros')" style="margin-bottom:8px;">
            <div class="checkbox-lista gf-lista-scroll" id="lista-membros">
                <?php foreach ($pessoas as $pessoa): ?>
                    <div class="checkbox-item gf-item">
                        <input type="checkbox" id="membro_<?php echo $pessoa['id']; ?>"
                               name="membros[]" value="<?php echo $pessoa['id']; ?>"
                               <?php echo in_array((string)$pessoa['id'], $_POST['membros'] ?? [], true) ? 'checked' : ''; ?>>
                        <label for="membro_<?php echo $pessoa['id']; ?>">
                            <?php echo htmlspecialchars($pessoa['nome']); ?>
                            <span class="gf-cargo">(<?php echo htmlspecialchars($pessoa['cargo']); ?>)</span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <button type="submit">Cadastrar Grupo Familiar</button>
</form>

<h2 style="margin-top:32px; margin-bottom:16px;">Grupos Familiares cadastrados</h2>

<?php if (count($grupos) === 0): ?>
    <p style="color:var(--color-text-muted); font-size:14px;">Nenhum Grupo Familiar cadastrado ainda.</p>
<?php else: ?>
    <div class="tabela-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Nome</th><th>Dia</th><th>Horário</th>
                    <th>Líderes</th><th>Membros</th><th>Local</th>
                    <th>Celeiro</th><th>Dom. Oração</th><th>Status</th><th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $domingos = [1=>'1º Dom',2=>'2º Dom',3=>'3º Dom',4=>'4º Dom',5=>'5º Dom'];
                foreach ($grupos as $grupo):
                    $ativo = (int)$grupo['ativo'] === 1;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($grupo['id']); ?></td>
                        <td><?php echo htmlspecialchars($grupo['nome']); ?></td>
                        <td><?php echo htmlspecialchars($grupo['dia_semana']); ?></td>
                        <td><?php echo htmlspecialchars($grupo['horario']); ?></td>
                        <td><?php echo htmlspecialchars($grupo['lideres'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($grupo['total_membros']); ?></td>
                        <td><?php echo htmlspecialchars($grupo['local_padrao'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($grupo['item_celeiro'] ?? '—'); ?></td>
                        <td><?php echo $grupo['domingo_oracao_culto'] ? htmlspecialchars($domingos[(int)$grupo['domingo_oracao_culto']] ?? '—') : '—'; ?></td>
                        <td>
                            <?php if ($ativo): ?>
                                <span class="status-ativo">Ativo</span>
                            <?php else: ?>
                                <span class="status-inativo">Desativado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="acoes" style="flex-direction:column; gap:6px;">
                                <a class="btn-gf btn-gf-editar" href="/grupos_familiares_editar.php?id=<?php echo $grupo['id']; ?>">Editar</a>
                                <?php if ($ativo): ?>
                                    <form method="POST" action="/grupos_familiares_desativar.php" class="form-acao"
                                          onsubmit="return confirm('Deseja desativar este GF?');">
                                        <input type="hidden" name="id" value="<?php echo $grupo['id']; ?>">
                                        <button type="submit" class="btn-gf btn-gf-desativar">Desativar</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/grupos_familiares_reativar.php" class="form-acao"
                                          onsubmit="return confirm('Deseja reativar este GF?');">
                                        <input type="hidden" name="id" value="<?php echo $grupo['id']; ?>">
                                        <button type="submit" class="btn-gf btn-gf-reativar">Reativar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

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
        sync(); fixo.addEventListener('change', sync);
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
