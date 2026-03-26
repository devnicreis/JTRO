<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<div class="page-header">
    <h1>Retiro de Integração</h1>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="GET" action="/retiro_integracao.php">
    <div class="grid">
        <div class="campo">
            <label for="grupo_id">GF de Integração</label>
            <select id="grupo_id" name="grupo_id" required>
                <option value="">Selecione</option>
                <?php foreach ($gruposIntegracao as $grupo): ?>
                    <option value="<?php echo (int) $grupo['id']; ?>" <?php echo $grupoId === (int) $grupo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($grupo['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="pessoa_id">Membro</label>
            <select id="pessoa_id" name="pessoa_id" <?php echo $grupoId > 0 ? 'required' : 'disabled'; ?>>
                <option value="">Selecione</option>
                <?php foreach ($membrosGrupo as $membro): ?>
                    <option value="<?php echo (int) $membro['id']; ?>" <?php echo $pessoaId === (int) $membro['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($membro['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php if ($retiroId > 0): ?>
        <input type="hidden" name="editar" value="<?php echo (int) $retiroId; ?>">
    <?php endif; ?>
    <button type="submit">Carregar membro</button>
</form>

<?php if ($grupoId > 0 && $pessoaId > 0): ?>
    <div class="presencas-card" style="margin-top:24px;">
        <h2 style="margin-bottom:20px;"><?php echo $retiroId > 0 ? 'Editar retiro registrado' : 'Registrar retiro de integração'; ?></h2>

        <form method="POST" action="/retiro_integracao.php">
            <input type="hidden" name="salvar_retiro_integracao" value="1">
            <input type="hidden" name="grupo_id" value="<?php echo (int) $grupoId; ?>">
            <input type="hidden" name="pessoa_id" value="<?php echo (int) $pessoaId; ?>">
            <?php if ($retiroId > 0): ?>
                <input type="hidden" name="retiro_id" value="<?php echo (int) $retiroId; ?>">
            <?php endif; ?>

            <div class="grid">
                <div class="campo">
                    <label>Membro que participou do retiro</label>
                    <input type="text" value="<?php
                        foreach ($membrosGrupo as $membro) {
                            if ((int) $membro['id'] === $pessoaId) {
                                echo htmlspecialchars($membro['nome']);
                                break;
                            }
                        }
                    ?>" readonly>
                </div>
                <div class="campo">
                    <label for="data_retiro">Data do retiro</label>
                    <input type="date" id="data_retiro" name="data_retiro" required value="<?php echo htmlspecialchars($dataRetiro); ?>">
                </div>
            </div>

            <div class="campo">
                <label>Aulas ministradas no retiro</label>
                <?php if (count($aulasDisponiveis) === 0): ?>
                    <p style="color:var(--color-text-muted); font-size:14px;">Este membro já concluiu todas as aulas disponíveis.</p>
                <?php else: ?>
                    <div class="tabela-wrapper" style="margin-top:8px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Marcar</th>
                                    <th>Código</th>
                                    <th>Tema da aula</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aulasDisponiveis as $aula): ?>
                                    <?php $codigo = (string) $aula['codigo']; ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="aulas[]" value="<?php echo htmlspecialchars($codigo); ?>"
                                                   <?php echo in_array($codigo, $aulasSelecionadas, true) || !empty($aula['selecionada']) ? 'checked' : ''; ?>>
                                        </td>
                                        <td><?php echo htmlspecialchars($codigo); ?></td>
                                        <td><?php echo htmlspecialchars($aula['titulo']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="presenca-acoes-btns">
                <button type="submit" class="btn-presenca-salvar"><?php echo $retiroId > 0 ? 'Salvar edição do retiro' : 'Salvar retiro'; ?></button>
                <?php if ($retiroId > 0): ?>
                    <a class="btn-presenca-oracao" href="/retiro_integracao.php?grupo_id=<?php echo (int) $grupoId; ?>&pessoa_id=<?php echo (int) $pessoaId; ?>">Cancelar edição</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="presencas-card" style="margin-top:24px;">
    <h2 style="margin-bottom:20px;">Retiros registrados</h2>

    <?php if (count($retiros) === 0): ?>
        <p style="color:var(--color-text-muted); font-size:14px;">Nenhum retiro de integração registrado ainda.</p>
    <?php else: ?>
        <div class="tabela-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>GF</th>
                        <th>Membro</th>
                        <th>Data do retiro</th>
                        <th>Aulas</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($retiros as $retiro): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($retiro['grupo_nome']); ?></td>
                            <td><?php echo htmlspecialchars($retiro['pessoa_nome']); ?></td>
                            <td><?php echo htmlspecialchars(formatarDataBr($retiro['data_retiro'])); ?></td>
                            <td><?php echo htmlspecialchars(implode(', ', $retiro['aulas_codigos'] ?? [])); ?></td>
                            <td>
                                <a class="btn-gf btn-gf-editar" href="/retiro_integracao.php?editar=<?php echo (int) $retiro['id']; ?>">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const grupo = document.getElementById('grupo_id');
    const pessoa = document.getElementById('pessoa_id');

    if (grupo) {
        grupo.addEventListener('change', function() {
            if (this.form) {
                const editar = this.form.querySelector('input[name="editar"]');
                if (editar) editar.remove();
                this.form.submit();
            }
        });
    }

    if (pessoa) {
        pessoa.addEventListener('change', function() {
            if (this.form) this.form.submit();
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
