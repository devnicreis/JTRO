<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/index.php">&larr; Voltar para in&iacute;cio</a>
</div>

<div class="page-header">
    <h1>Auditoria</h1>
</div>

<form method="GET" action="/auditoria.php" id="formAuditoriaFiltros">
    <input type="hidden" name="pagina" value="1">

    <div class="grid">
        <div class="campo">
            <label for="usuario_id">L&iacute;der / Usu&aacute;rio</label>
            <select id="usuario_id" name="usuario_id">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo $usuarioId === (int)$u['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="grupo_familiar_id">Grupo Familiar</label>
            <select id="grupo_familiar_id" name="grupo_familiar_id">
                <option value="">Todos</option>
                <?php foreach ($grupos as $grupo): ?>
                    <option value="<?php echo $grupo['id']; ?>" <?php echo $grupoFamiliarId === (int)$grupo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($grupo['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="data_alteracao_inicio">Data inicial da altera&ccedil;&atilde;o</label>
            <input type="date" id="data_alteracao_inicio" name="data_alteracao_inicio" value="<?php echo htmlspecialchars($dataAlteracaoInicio); ?>">
        </div>

        <div class="campo">
            <label for="data_alteracao_fim">Data final da altera&ccedil;&atilde;o</label>
            <input type="date" id="data_alteracao_fim" name="data_alteracao_fim" value="<?php echo htmlspecialchars($dataAlteracaoFim); ?>">
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="data_reuniao">Data da reuni&atilde;o</label>
            <input type="date" id="data_reuniao" name="data_reuniao" value="<?php echo htmlspecialchars($dataReuniao); ?>">
        </div>

        <div class="campo">
            <label for="limite">Exibir por p&aacute;gina</label>
            <select id="limite" name="limite">
                <option value="20"  <?php echo $limite === 20  ? 'selected' : ''; ?>>20 linhas</option>
                <option value="50"  <?php echo $limite === 50  ? 'selected' : ''; ?>>50 linhas</option>
                <option value="100" <?php echo $limite === 100 ? 'selected' : ''; ?>>100 linhas</option>
            </select>
        </div>
    </div>

    <div class="acoes">
        <button type="submit">Aplicar filtros</button>
        <a class="botao-link botao-secundario" href="/auditoria.php">Limpar filtros</a>
    </div>
</form>

<div class="presencas-card">
    <h2>
        Resultados
        <span style="font-size: 12px; font-weight: 400; color: var(--color-text-muted); text-transform: none; letter-spacing: 0;">
            <?php echo (int) $totalLogs; ?> registro<?php echo (int) $totalLogs !== 1 ? 's' : ''; ?>
        </span>
    </h2>

    <?php if (count($logs) === 0): ?>
        <p style="color: var(--color-text-muted); font-size: 13px;">Nenhum registro encontrado para os filtros informados.</p>
    <?php else: ?>
        <div class="auditoria-tabela-wrapper">
            <table class="auditoria-tabela">
                <thead>
                    <tr>
                        <th>Data da altera&ccedil;&atilde;o</th>
                        <th>Usu&aacute;rio</th>
                        <th>A&ccedil;&atilde;o</th>
                        <th>Entidade</th>
                        <th>GF</th>
                        <th>Data da reuni&atilde;o</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(formatarDataHoraBr($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['usuario_nome'] ?? 'Sistema'); ?></td>
                            <td><?php echo htmlspecialchars($log['acao']); ?></td>
                            <td><?php echo htmlspecialchars($log['entidade']); ?></td>
                            <td><?php echo htmlspecialchars($log['grupo_nome'] ?? '&mdash;'); ?></td>
                            <td><?php echo htmlspecialchars(formatarDataBr($log['reuniao_data'] ?? null)); ?></td>
                            <td><?php echo htmlspecialchars($log['detalhes'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php
    $parametrosPaginacao = [
        'usuario_id' => $usuarioId > 0 ? $usuarioId : '',
        'grupo_familiar_id' => $grupoFamiliarId > 0 ? $grupoFamiliarId : '',
        'data_alteracao_inicio' => $dataAlteracaoInicio,
        'data_alteracao_fim' => $dataAlteracaoFim,
        'data_reuniao' => $dataReuniao,
        'limite' => $limite,
    ];
    $parametrosPaginacao = array_filter($parametrosPaginacao, static function ($valor): bool {
        return $valor !== '' && $valor !== null;
    });
    ?>

    <?php if ($totalLogs > 0): ?>
        <div class="acoes" style="margin-top: 14px; justify-content: space-between; gap: 12px;">
            <span style="font-size: 12px; color: var(--color-text-muted);">
                P&aacute;gina <?php echo (int) $pagina; ?> de <?php echo (int) $totalPaginas; ?>
            </span>
            <div class="acoes" style="margin-left: auto;">
                <?php if ($pagina > 1): ?>
                    <?php $queryAnterior = http_build_query(array_merge($parametrosPaginacao, ['pagina' => $pagina - 1])); ?>
                    <a class="botao-link botao-secundario" href="/auditoria.php?<?php echo htmlspecialchars($queryAnterior); ?>">P&aacute;gina anterior</a>
                <?php endif; ?>
                <?php if ($pagina < $totalPaginas): ?>
                    <?php $queryProxima = http_build_query(array_merge($parametrosPaginacao, ['pagina' => $pagina + 1])); ?>
                    <a class="botao-link botao-secundario" href="/auditoria.php?<?php echo htmlspecialchars($queryProxima); ?>">Pr&oacute;xima p&aacute;gina</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

