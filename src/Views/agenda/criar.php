<?php require __DIR__ . '/../layouts/header.php'; ?>
<div class="page-header">
    <h1><?php echo isset($evento) ? 'Editar Evento' : 'Novo Evento'; ?></h1>
    <a href="/agenda.php" style="font-size:13px; color:var(--color-text-muted);">← Voltar para Agenda</a>
</div>
<?php if (!empty($erro)): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>
<?php
$isEdicao   = isset($evento);
$actionUrl  = $isEdicao ? '/agenda_editar.php' : '/agenda_criar.php';
$titulo     = $isEdicao ? $evento['titulo']                      : ($_GET['titulo'] ?? '');
$data       = $isEdicao ? $evento['data']                        : ($_GET['data'] ?? date('Y-m-d'));
$horaInicio = $isEdicao ? ($evento['hora_inicio'] ?? $evento['horario'] ?? '') : '';
$horaFim    = $isEdicao ? ($evento['hora_fim']    ?? $evento['horario_fim'] ?? '') : '';
$deptAtual  = $isEdicao ? ($evento['departamento'] ?? '') : '';
$descricao  = $isEdicao ? ($evento['descricao'] ?? '') : '';

// Normaliza para comparação sem acento
function normalizar(string $s): string {
    return mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $s));
}
?>
<form method="POST" action="<?php echo $actionUrl; ?>">
    <?php if ($isEdicao): ?>
        <input type="hidden" name="id" value="<?php echo $evento['id']; ?>">
    <?php endif; ?>

    <div class="campo">
        <label for="titulo">Título do evento <span style="color:var(--color-red);">*</span></label>
        <input type="text" id="titulo" name="titulo" required maxlength="120"
               value="<?php echo htmlspecialchars($titulo); ?>"
               placeholder="Ex: Culto de Curas e Libertação">
    </div>

    <div class="grid">
        <div class="campo">
            <label for="data">Data <span style="color:var(--color-red);">*</span></label>
            <input type="date" id="data" name="data" required
                   value="<?php echo htmlspecialchars($data); ?>">
        </div>
        <div class="campo">
            <label for="departamento">Departamento responsável <span style="color:var(--color-red);">*</span></label>
            <select id="departamento" name="departamento" required>
                <option value="">Selecione</option>
                <?php foreach ($departamentos as $d): ?>
                    <?php $selecionado = normalizar($deptAtual) === normalizar($d) ? 'selected' : ''; ?>
                    <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $selecionado; ?>>
                        <?php echo htmlspecialchars($d); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="hora_inicio">Horário de início <span style="color:var(--color-red);">*</span></label>
            <input type="time" id="hora_inicio" name="hora_inicio" required
                   value="<?php echo htmlspecialchars($horaInicio); ?>">
        </div>
        <div class="campo">
            <label for="hora_fim">Horário de término <span class="escala-hint">(opcional)</span></label>
            <input type="time" id="hora_fim" name="hora_fim"
                   value="<?php echo htmlspecialchars($horaFim); ?>">
        </div>
    </div>

    <div class="campo">
        <label for="descricao">Descrição <span class="escala-hint">(opcional)</span></label>
        <textarea id="descricao" name="descricao" maxlength="500"
                  placeholder="Informações adicionais sobre o evento..."><?php echo htmlspecialchars($descricao); ?></textarea>
        <small>Máximo 500 caracteres.</small>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="submit" class="btn-presenca-oracao" style="max-width:220px;">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 8l4 4 8-8"/></svg>
            <?php echo $isEdicao ? 'Salvar alterações' : 'Criar evento'; ?>
        </button>
        <a href="/agenda.php" class="botao-link botao-secundario"
           style="min-height:38px; display:inline-flex; align-items:center; padding:0 16px; font-size:13px;">
            Cancelar
        </a>
    </div>
</form>
<?php require __DIR__ . '/../layouts/footer.php'; ?>