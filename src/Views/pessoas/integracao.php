<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<div class="menu">
    <a href="/pessoas.php">&larr; Voltar para Pessoas</a>
</div>

<div class="page-header">
    <h1>Aulas de Integração</h1>
</div>

<div class="card" style="padding:20px; margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <div style="font-size:13px; color:var(--color-text-muted);">Pessoa</div>
            <div style="font-size:20px; font-weight:700;"><?php echo htmlspecialchars($pessoa['nome']); ?></div>
            <div style="font-size:13px; color:var(--color-text-muted);">
                GF: <?php echo htmlspecialchars($pessoa['grupo_familiar_nome'] ?? '—'); ?>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:13px; color:var(--color-text-muted);">Progresso</div>
            <div style="font-size:20px; font-weight:700;"><?php echo (int) $totalConcluidas; ?>/<?php echo count($progresso); ?></div>
            <div style="font-size:13px; color:var(--color-text-muted);">
                Concluiu: <?php echo htmlspecialchars(labelSimNao((int) ($pessoa['concluiu_integracao'] ?? 0))); ?>
            </div>
        </div>
    </div>
</div>

<?php if ((int) ($pessoa['integracao_conclusao_manual'] ?? 0) === 1 && (int) ($pessoa['concluiu_integracao'] ?? 0) === 1): ?>
    <div class="mensagem" style="margin-bottom:20px;">
        INTEGRAÇÃO CONCLUÍDA.
    </div>
<?php endif; ?>

<div class="tabela-wrapper">
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Aula</th>
                <th>Status</th>
                <th>Data</th>
                <th>Origem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($progresso as $aula): ?>
                <tr>
                    <td><?php echo htmlspecialchars($aula['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($aula['titulo']); ?></td>
                    <td>
                        <?php if (!empty($aula['concluida'])): ?>
                            <span class="status-ativo">Concluída</span>
                        <?php else: ?>
                            <span class="status-inativo">Pendente</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(formatarDataBr($aula['data_aula'] ?? null)); ?></td>
                    <td>
                        <?php
                        $origem = $aula['origem'] ?? '';
                        echo htmlspecialchars($origem === 'reuniao' ? 'Reunião' : ($origem === 'retiro' ? 'Retiro' : '—'));
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
