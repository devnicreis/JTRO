<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
$perfisGrupo = opcoesPerfilGrupo();
$dias = ['segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
$domingosFiltro = [1 => '1º Domingo', 2 => '2º Domingo', 3 => '3º Domingo', 4 => '4º Domingo', 5 => '5º Domingo'];
?>

<div class="page-header">
    <h1>GFs Cadastrados</h1>
    <p class="page-header-subtitulo">Consulte os grupos com rolagem lateral e vertical, mantendo cada dado em sua própria coluna.</p>
</div>

<div class="acoes" style="margin-bottom: 16px;">
    <a href="/grupos_familiares.php" class="botao-link botao-secundario">Ir para cadastro</a>
</div>

<form method="GET" action="/grupos_familiares_cadastrados.php" id="filtrosGruposTabela"></form>
<div class="tabela-wrapper tabela-listagem-limitada">
    <table class="tabela-gfs">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Dia</th>
                <th>Horário</th>
                <th>Perfil</th>
                <th>Local padrão</th>
                <th>Local fixo</th>
                <th>Celeiro</th>
                <th>Dom. oração</th>
                <th>Líderes</th>
                <th>Membros</th>
                <th>Status</th>
                <th class="tabela-acoes">Ações</th>
            </tr>
            <tr class="filtros-linha">
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="text" name="id" value="<?php echo htmlspecialchars($filtros['id'] ?? ''); ?>" placeholder="ID"></th>
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="text" name="nome" value="<?php echo htmlspecialchars($filtros['nome'] ?? ''); ?>" placeholder="Nome"></th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosGruposTabela" name="dia_semana">
                        <option value="">Todos</option>
                        <?php foreach ($dias as $dia): ?>
                            <option value="<?php echo htmlspecialchars($dia); ?>" <?php echo (($filtros['dia_semana'] ?? '') === $dia) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($dia)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="time" name="horario" value="<?php echo htmlspecialchars($filtros['horario'] ?? ''); ?>"></th>
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
                        <?php foreach ($domingosFiltro as $valor => $label): ?>
                            <option value="<?php echo $valor; ?>" <?php echo (($filtros['domingo_oracao_culto'] ?? '') === (string) $valor) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="text" name="lideres" value="<?php echo htmlspecialchars($filtros['lideres'] ?? ''); ?>" placeholder="Líderes"></th>
                <th><input class="tabela-filtro-campo" form="filtrosGruposTabela" type="text" name="membros" value="<?php echo htmlspecialchars($filtros['membros'] ?? ''); ?>" placeholder="Membros"></th>
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
                        <a class="botao-link botao-secundario" href="/grupos_familiares_cadastrados.php">Limpar</a>
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
                <?php $membrosLista = array_filter(array_map('trim', explode(',', (string) ($grupo['membros_nomes'] ?? '')))); ?>
                <tr>
                    <td><?php echo (int) $grupo['id']; ?></td>
                    <td><?php echo htmlspecialchars($grupo['nome']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst((string) $grupo['dia_semana'])); ?></td>
                    <td><?php echo htmlspecialchars($grupo['horario']); ?></td>
                    <td><?php echo htmlspecialchars(labelPerfilGrupo($grupo['perfil_grupo'] ?? null)); ?></td>
                    <td><?php echo htmlspecialchars($grupo['local_padrao'] ?: '—'); ?></td>
                    <td><?php echo htmlspecialchars(labelSimNao((int) ($grupo['local_fixo'] ?? 0))); ?></td>
                    <td><?php echo htmlspecialchars($grupo['item_celeiro'] ?: '—'); ?></td>
                    <td><?php echo !empty($grupo['domingo_oracao_culto']) ? htmlspecialchars($domingosFiltro[(int) $grupo['domingo_oracao_culto']] ?? '—') : '—'; ?></td>
                    <td><?php echo htmlspecialchars($grupo['lideres'] ?: '—'); ?></td>
                    <td>
                        <div><?php echo (int) ($grupo['total_membros'] ?? 0); ?></div>
                        <?php if (count($membrosLista) > 0): ?>
                            <div class="tabela-tooltip">
                                <span class="tabela-tooltip-acionador">Ver membros</span>
                                <div class="tabela-tooltip-conteudo">
                                    <strong style="display:block; margin-bottom:8px;">Membros do GF</strong>
                                    <ul class="tabela-tooltip-lista">
                                        <?php foreach ($membrosLista as $membro): ?>
                                            <li><?php echo htmlspecialchars($membro); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int) $grupo['ativo'] === 1): ?>
                            <span class="status-ativo">Ativo</span>
                        <?php else: ?>
                            <span class="status-inativo">Desativado</span>
                            <?php if (!empty($grupo['motivo_desativacao'])): ?>
                                <div class="notif-detalhe"><?php echo htmlspecialchars($grupo['motivo_desativacao']); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="tabela-acoes">
                        <div class="acoes acoes-tabela-inline" style="flex-direction: column; gap: 6px;">
                            <a class="btn-gf btn-gf-editar" href="/grupos_familiares_editar.php?id=<?php echo (int) $grupo['id']; ?>">Editar</a>
                            <?php if ((int) $grupo['ativo'] === 1): ?>
                                <a class="btn-gf btn-gf-desativar" href="/grupos_familiares_desativar.php?id=<?php echo (int) $grupo['id']; ?>">Desativar</a>
                            <?php else: ?>
                                <form method="POST" action="/grupos_familiares_reativar.php" class="form-acao" onsubmit="return confirm('Deseja reativar este GF?');">
                                    <input type="hidden" name="id" value="<?php echo (int) $grupo['id']; ?>">
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
