<?php
// Partial compartilhado por criar.php e editar.php
$isEdicao    = !empty($evento);
$titulo      = $isEdicao ? $evento['titulo']      : '';
$data        = $isEdicao ? $evento['data']        : ($_GET['data'] ?? date('Y-m-d'));
$horario     = $isEdicao ? $evento['horario']     : '';
$horarioFim  = $isEdicao ? ($evento['horario_fim'] ?? '') : '';
$departamento = $isEdicao ? $evento['departamento'] : '';
$descricao   = $isEdicao ? ($evento['descricao'] ?? '') : '';
?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <h1><?php echo $isEdicao ? 'Editar Evento' : 'Novo Evento'; ?></h1>
    <a href="/agenda.php" style="font-size:13px; color:var(--color-text-muted);">← Voltar para Agenda</a>
</div>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo $isEdicao ? '/agenda_editar.php' : '/agenda_criar.php'; ?>">
    <?php if ($isEdicao): ?>
        <input type="hidden" name="id" value="<?php echo (int)$evento['id']; ?>">
    <?php endif; ?>

    <div class="campo">
        <label for="titulo">Título do evento <span style="color:var(--color-red-mid);">*</span></label>
        <input type="text" id="titulo" name="titulo" required maxlength="120"
               value="<?php echo htmlspecialchars($titulo); ?>"
               placeholder="Ex: Culto de Sábado">
    </div>

    <div class="grid">
        <div class="campo">
            <label for="data">Data <span style="color:var(--color-red-mid);">*</span></label>
            <input type="date" id="data" name="data" required
                   value="<?php echo htmlspecialchars($data); ?>">
        </div>
        <div class="campo">
            <label for="departamento">Departamento responsável <span style="color:var(--color-red-mid);">*</span></label>
            <select id="departamento" name="departamento" required>
                <option value="">Selecione</option>
                <?php foreach (AgendaRepository::DEPARTAMENTOS as $d): ?>
                    <option value="<?php echo htmlspecialchars($d); ?>"
                        <?php echo $departamento === $d ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="horario">Horário de início <span style="color:var(--color-red-mid);">*</span></label>
            <input type="time" id="horario" name="horario" required
                   value="<?php echo htmlspecialchars($horario); ?>">
        </div>
        <div class="campo">
            <label for="horario_fim">Horário de término <span style="font-weight:400; color:var(--color-text-muted);">(opcional)</span></label>
            <input type="time" id="horario_fim" name="horario_fim"
                   value="<?php echo htmlspecialchars($horarioFim); ?>">
        </div>
    </div>

    <div class="campo">
        <label for="descricao">Descrição <span style="font-weight:400; color:var(--color-text-muted);">(opcional)</span></label>
        <textarea id="descricao" name="descricao" maxlength="500"
                  placeholder="Detalhes, local, observações..."><?php echo htmlspecialchars($descricao); ?></textarea>
        <small>Máximo 500 caracteres.</small>
    </div>

    <div style="display:flex; gap:10px;">
        <button type="submit" class="btn-presenca-salvar" style="max-width:220px;">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 8l4 4 8-8"/></svg>
            <?php echo $isEdicao ? 'Salvar alterações' : 'Criar evento'; ?>
        </button>
        <a href="/agenda.php" class="botao-link botao-secundario" style="max-width:140px;">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
