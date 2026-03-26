<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
$perfisGrupo = opcoesPerfilGrupo();
$dias = ['segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado','domingo'];
$domingosFiltro = [1 => '1º Domingo', 2 => '2º Domingo', 3 => '3º Domingo', 4 => '4º Domingo', 5 => '5º Domingo'];
?>

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
        <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
    </div>

    <div class="grid">
        <div class="campo">
            <label for="dia_semana">Dia da semana</label>
            <select id="dia_semana" name="dia_semana" required>
                <option value="">Selecione</option>
                <?php $diaSel = $_POST['dia_semana'] ?? ''; ?>
                <?php foreach ($dias as $dia): ?>
                    <option value="<?php echo $dia; ?>" <?php echo $diaSel === $dia ? 'selected' : ''; ?>>
                        <?php echo ucfirst($dia); ?>
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
                <label for="item_celeiro">
                    Item do Projeto Celeiro
                    <span class="escala-hint">ex: Arroz, Feijão, Macarrão</span>
                </label>
                <input type="text" id="item_celeiro" name="item_celeiro" placeholder="Ex: Arroz"
                       value="<?php echo htmlspecialchars($_POST['item_celeiro'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="domingo_oracao_culto">Domingo de Oração antes do Culto</label>
                <select id="domingo_oracao_culto" name="domingo_oracao_culto">
                    <option value="0">Não escalonado</option>
                    <?php $domSel = (int) ($_POST['domingo_oracao_culto'] ?? 0); ?>
                    <?php foreach ($domingosFiltro as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo $domSel === $val ? 'selected' : ''; ?>>
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
                               <?php echo in_array((string) $pessoa['id'], $_POST['lideres'] ?? [], true) ? 'checked' : ''; ?>>
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
                               <?php echo in_array((string) $pessoa['id'], $_POST['membros'] ?? [], true) ? 'checked' : ''; ?>>
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

<h2 style="margin-top:32px; margin-bottom:16px; display:none;">Filtros</h2>

<form method="GET" action="/grupos_familiares.php" id="filtrosGruposLegado" style="display:none;">
    <div class="grid">
        <div class="campo">
            <label for="filtro_id">ID</label>
            <input type="text" id="filtro_id" name="id" value="<?php echo htmlspecialchars($filtros['id'] ?? ''); ?>">
        </div>
        <div class="campo">
            <label for="filtro_nome">Nome</label>
            <input type="text" id="filtro_nome" name="nome" value="<?php echo htmlspecialchars($filtros['nome'] ?? ''); ?>">
        </div>
    </div>
</form>

<h2 style="margin-top:32px; margin-bottom:16px;">Grupos Familiares cadastrados</h2>

<form method="GET" action="/grupos_familiares.php" id="filtrosGruposTabela"></form>
<div class="tabela-wrapper tabela-cadastro-completa">
    <table class="tabela-gfs tabela-cadastro-grid">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Dia</th>
                <th>Horário</th>
                <th>Líderes</th>
                <th>Membros</th>
                <th>Perfil</th>
                <th>Local</th>
                <th>Local Fixo</th>
                <th>Celeiro</th>
                <th>Dom. Oração</th>
                <th>Status</th>
                <th class="tabela-acoes">Ações</th>
            </tr>
            <tr class="filtros-linha">
                <th><input class="tabela-filtro-campo tabela-filtro-input-curto" form="filtrosGruposTabela" type="text" name="id" value="<?php echo htmlspecialchars($filtros['id'] ?? ''); ?>" placeholder="ID"></th>
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="text" name="nome" value="<?php echo htmlspecialchars($filtros['nome'] ?? ''); ?>" placeholder="Nome"></th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosGruposTabela" name="dia_semana">
                        <option value="">Todos</option>
                        <?php foreach ($dias as $dia): ?>
                            <option value="<?php echo $dia; ?>" <?php echo (($filtros['dia_semana'] ?? '') === $dia) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($dia); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="time" name="horario" value="<?php echo htmlspecialchars($filtros['horario'] ?? ''); ?>"></th>
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="text" name="lideres" value="<?php echo htmlspecialchars($filtros['lideres'] ?? ''); ?>" placeholder="Lí­deres"></th>
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="text" name="membros" value="<?php echo htmlspecialchars($filtros['membros'] ?? ''); ?>" placeholder="Membros"></th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosGruposTabela" name="perfil_grupo">
                        <option value="">Todos</option>
                        <?php foreach ($perfisGrupo as $valor => $label): ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($filtros['perfil_grupo'] ?? '') === $valor) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="text" name="local_padrao" value="<?php echo htmlspecialchars($filtros['local_padrao'] ?? ''); ?>" placeholder="Local"></th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosGruposTabela" name="local_fixo">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($filtros['local_fixo'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?php echo (($filtros['local_fixo'] ?? '') === '0') ? 'selected' : ''; ?>>Não</option>
                    </select>
                </th>
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="text" name="item_celeiro" value="<?php echo htmlspecialchars($filtros['item_celeiro'] ?? ''); ?>" placeholder="Celeiro"></th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosGruposTabela" name="domingo_oracao_culto">
                        <option value="">Todos</option>
                        <?php foreach ($domingosFiltro as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo (($filtros['domingo_oracao_culto'] ?? '') === (string) $val) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosGruposTabela" name="status">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($filtros['status'] ?? '') === '1') ? 'selected' : ''; ?>>Ativo</option>
                        <option value="0" <?php echo (($filtros['status'] ?? '') === '0') ? 'selected' : ''; ?>>Desativado</option>
                    </select>
                </th>
                <th>
                    <div class="tabela-filtro-acoes">
                        <button type="submit" form="filtrosGruposTabela">Filtrar</button>
                        <a class="botao-link botao-secundario" href="/grupos_familiares.php">Limpar</a>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($grupos) === 0): ?>
                <tr>
                    <td colspan="13" class="tabela-vazia">Nenhum GF encontrado para o filtro atual.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($grupos as $grupo): ?>
                <?php $ativo = (int) $grupo['ativo'] === 1; ?>
                <tr>
                    <td><?php echo htmlspecialchars($grupo['id']); ?></td>
                    <td><div class="tabela-coluna-principal"><?php echo htmlspecialchars($grupo['nome']); ?></div></td>
                    <td><?php echo htmlspecialchars(ucfirst((string) $grupo['dia_semana'])); ?></td>
                    <td><?php echo htmlspecialchars($grupo['horario']); ?></td>
                    <td><?php echo htmlspecialchars($grupo['lideres'] ?: '-'); ?></td>
                    <td>
                        <div><?php echo htmlspecialchars($grupo['total_membros']); ?></div>
                        <?php if (!empty($grupo['membros_nomes'])): ?>
                            <?php $membrosLista = array_filter(array_map('trim', explode(',', (string) $grupo['membros_nomes']))); ?>
                            <div class="tabela-tooltip">
                                <span class="tabela-tooltip-acionador">Ver membros</span>
                                <div class="tabela-tooltip-conteudo">
                                    <strong style="display:block; margin-bottom:8px;">Membros do GF</strong>
                                    <ul class="tabela-tooltip-lista">
                                        <?php foreach ($membrosLista as $membroNome): ?>
                                            <li><?php echo htmlspecialchars($membroNome); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-blue"><?php echo htmlspecialchars(labelPerfilGrupo($grupo['perfil_grupo'] ?? null)); ?></span></td>
                    <td><?php echo htmlspecialchars($grupo['local_padrao'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars(labelSimNao((int) ($grupo['local_fixo'] ?? 0))); ?></td>
                    <td><?php echo htmlspecialchars($grupo['item_celeiro'] ?: '-'); ?></td>
                    <td><?php echo $grupo['domingo_oracao_culto'] ? htmlspecialchars($domingosFiltro[(int) $grupo['domingo_oracao_culto']] ?? '-') : '-'; ?></td>
                    <td>
                        <?php if ($ativo): ?>
                            <span class="status-ativo">Ativo</span>
                        <?php else: ?>
                            <span class="status-inativo">Desativado</span>
                            <?php if (!empty($grupo['motivo_desativacao'])): ?>
                                <div class="notif-motivo"><?php echo htmlspecialchars($grupo['motivo_desativacao']); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="tabela-acoes">
                        <div class="acoes" style="flex-direction:column; gap:6px;">
                            <a class="btn-gf btn-gf-editar" href="/grupos_familiares_editar.php?id=<?php echo $grupo['id']; ?>">Editar</a>
                            <?php if ($ativo): ?>
                                <a class="btn-gf btn-gf-desativar" href="/grupos_familiares_desativar.php?id=<?php echo $grupo['id']; ?>">Desativar</a>
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
