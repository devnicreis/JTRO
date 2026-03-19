<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/index.php">← Voltar para dashboard</a>
</div>

<h1>Auditoria</h1>

<form method="GET" action="/auditoria.php">
    <div class="grid">
        <div class="campo">
            <label for="usuario_id">Líder / Usuário</label>
            <select id="usuario_id" name="usuario_id">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $usuario): ?>
                    <option value="<?php echo $usuario['id']; ?>" <?php echo $usuarioId === (int)$usuario['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($usuario['nome']); ?>
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
            <label for="data_alteracao_inicio">Data inicial da alteração</label>
            <input type="date" id="data_alteracao_inicio" name="data_alteracao_inicio" value="<?php echo htmlspecialchars($dataAlteracaoInicio); ?>">
        </div>

        <div class="campo">
            <label for="data_alteracao_fim">Data final da alteração</label>
            <input type="date" id="data_alteracao_fim" name="data_alteracao_fim" value="<?php echo htmlspecialchars($dataAlteracaoFim); ?>">
        </div>
    </div>

    <div class="campo">
        <label for="data_reuniao">Data da reunião</label>
        <input type="date" id="data_reuniao" name="data_reuniao" value="<?php echo htmlspecialchars($dataReuniao); ?>">
    </div>

    <div class="acoes">
        <button type="submit">Aplicar filtros</button>
        <a class="botao-link botao-secundario" href="/auditoria.php">Limpar todos os filtros</a>
    </div>
</form>

<div class="presencas-card">
    <h2>Resultados</h2>

    <?php if (count($logs) === 0): ?>
        <p>Nenhum registro encontrado para os filtros informados.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Data da alteração</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Entidade</th>
                    <th>GF</th>
                    <th>Data da reunião</th>
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
                        <td><?php echo htmlspecialchars($log['grupo_nome'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars(formatarDataBr($log['reuniao_data'] ?? null)); ?></td>
                        <td><?php echo htmlspecialchars($log['detalhes'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>