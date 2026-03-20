<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<div class="page-header">
    <h1>Reuniões e Presenças</h1>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>
<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<!-- Seletor de GF e data -->
<form method="GET" action="/presencas.php" id="formCarregar">
    <div class="grid">
        <div class="campo">
            <label for="grupo_id">Grupo Familiar</label>
            <select id="grupo_id" name="grupo_id" required>
                <option value="">Selecione</option>
                <?php foreach ($grupos as $grupo): ?>
                    <option value="<?php echo $grupo['id']; ?>"
                        <?php echo $grupoId === (int)$grupo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($grupo['nome']); ?> — <?php echo htmlspecialchars($grupo['dia_semana']); ?> às <?php echo htmlspecialchars($grupo['horario']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="data">Data da reunião</label>
            <input type="date" id="data" name="data" required
                   value="<?php echo htmlspecialchars($data); ?>">
            <div id="erro-data" class="erro" style="display:none; margin-top:8px;">
                A reunião só pode ser criada para hoje ou até 30 dias atrás.
            </div>
        </div>
    </div>
    <button type="submit">Carregar reunião</button>
</form>

<?php if ($modoNovaReuniao && !empty($membrosGrupo)): ?>

    <!-- ── Nova reunião: formulário completo (ainda não salvo no banco) ── -->
    <div class="presencas-card" style="margin-top:24px;" id="formNovaReuniao">
        <h2 style="margin-bottom:20px;">Nova reunião</h2>

        <form method="POST" action="/presencas.php" id="formSalvarNova">
            <input type="hidden" name="salvar_reuniao_nova" value="1">
            <input type="hidden" name="grupo_id" value="<?php echo $grupoId; ?>">
            <input type="hidden" name="data" value="<?php echo htmlspecialchars($data); ?>">

            <div class="grid" style="margin-bottom:0;">
                <div class="campo">
                    <label for="horario_criacao">Horário da reunião</label>
                    <input type="time" id="horario_criacao" name="horario_criacao" required
                           value="<?php echo htmlspecialchars($resumoGrupoHorario['horario'] ?? ''); ?>">
                    <small>Se diferente do padrão do GF, será sinalizado no sistema.</small>
                </div>
                <div class="campo">
                    <label for="local">Local</label>
                    <input type="text" id="local" name="local" maxlength="25" required
                           value="<?php echo htmlspecialchars($_POST['local'] ?? ($resumoGrupoHorario['local_padrao'] ?? '')); ?>">
                </div>
            </div>

            <div class="campo">
                <label for="observacoes">Observações <span style="font-weight:400; color:var(--color-text-muted);">(opcional)</span></label>
                <textarea id="observacoes" name="observacoes" maxlength="255"
                          style="min-height:70px;"><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
            </div>

            <!-- Tabela de presenças com toggle -->
            <div class="tabela-wrapper" style="margin-top:4px; margin-bottom:20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th style="text-align:center;">Presença</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membrosGrupo as $membro): ?>
                            <?php $statusPost = $_POST['presencas'][$membro['id']] ?? ''; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($membro['nome']); ?></td>
                                <td style="text-align:center; vertical-align:middle;">
                                    <div class="presenca-switch-wrap">
                                        <input type="radio"
                                               id="pres_<?php echo $membro['id']; ?>"
                                               name="presencas[<?php echo $membro['id']; ?>]"
                                               value="presente" class="presenca-radio"
                                               <?php echo $statusPost === 'presente' ? 'checked' : ''; ?>>
                                        <label for="pres_<?php echo $membro['id']; ?>"
                                               class="presenca-toggle presenca-toggle-pres">Presente</label>

                                        <input type="radio"
                                               id="aus_<?php echo $membro['id']; ?>"
                                               name="presencas[<?php echo $membro['id']; ?>]"
                                               value="ausente" class="presenca-radio"
                                               <?php echo $statusPost === 'ausente' ? 'checked' : ''; ?>>
                                        <label for="aus_<?php echo $membro['id']; ?>"
                                               class="presenca-toggle presenca-toggle-aus">Ausente</label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="presenca-acoes-btns">
                <button type="submit" class="btn-presenca-salvar" id="btnSalvarNova">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 5l-7 7-3-3"/></svg>
                    Salvar Reunião
                </button>
            </div>
        </form>
    </div>

<?php elseif ($reuniao && count($listaPresencas) > 0): ?>

    <!-- ── Reunião existente: editar presenças ── -->
    <div class="presencas-card" style="margin-top:24px;">
        <h2 style="margin-bottom:20px;">
            <?php echo htmlspecialchars($reuniao['grupo_nome']); ?>
            <span style="color:var(--color-text-muted); font-weight:400;"> — </span>
            <?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?>
        </h2>

        <form method="POST" action="/presencas.php">
            <input type="hidden" name="salvar_presencas" value="1">
            <input type="hidden" name="reuniao_id" value="<?php echo $reuniao['id']; ?>">
            <input type="hidden" name="grupo_id" value="<?php echo $grupoId; ?>">
            <input type="hidden" name="data" value="<?php echo htmlspecialchars($data); ?>">

            <div class="grid" style="margin-bottom:0;">
                <div class="campo">
                    <label for="local">Local</label>
                    <input type="text" id="local" name="local" maxlength="25"
                           value="<?php echo htmlspecialchars($reuniao['local'] ?? ''); ?>">
                </div>
                <div class="campo">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" maxlength="255"
                              style="min-height:70px;"><?php echo htmlspecialchars($reuniao['observacoes'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="tabela-wrapper" style="margin-top:16px; margin-bottom:20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th style="text-align:center;">Presença</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaPresencas as $presenca): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($presenca['nome']); ?></td>
                                <td style="text-align:center; vertical-align:middle;">
                                    <div class="presenca-switch-wrap">
                                        <input type="radio"
                                               id="pres_<?php echo $presenca['id']; ?>"
                                               name="presencas[<?php echo $presenca['id']; ?>]"
                                               value="presente" class="presenca-radio"
                                               <?php echo $presenca['status'] === 'presente' ? 'checked' : ''; ?>>
                                        <label for="pres_<?php echo $presenca['id']; ?>"
                                               class="presenca-toggle presenca-toggle-pres">Presente</label>

                                        <input type="radio"
                                               id="aus_<?php echo $presenca['id']; ?>"
                                               name="presencas[<?php echo $presenca['id']; ?>]"
                                               value="ausente" class="presenca-radio"
                                               <?php echo $presenca['status'] === 'ausente' ? 'checked' : ''; ?>>
                                        <label for="aus_<?php echo $presenca['id']; ?>"
                                               class="presenca-toggle presenca-toggle-aus">Ausente</label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="presenca-acoes-btns">
                <button type="submit" class="btn-presenca-salvar">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 5l-7 7-3-3"/></svg>
                    Salvar presenças
                </button>

                <?php if (!$presencasPendentes): ?>
                    <a class="btn-presenca-oracao" href="/pedidos_oracao.php?reuniao_id=<?php echo (int)$reuniao['id']; ?>">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M8 2v12M2 8h12"/></svg>
                        Pedidos de Oração
                    </a>
                <?php else: ?>
                    <div class="presenca-oracao-bloqueado">
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="10" height="8" rx="2"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>
                        Salve as presenças para liberar os Pedidos de Oração
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação de data
    const campoData = document.getElementById('data');
    const erroData  = document.getElementById('erro-data');
    if (campoData) {
        const pad = n => String(n).padStart(2,'0');
        const hoje = new Date();
        const hojeStr = `${hoje.getFullYear()}-${pad(hoje.getMonth()+1)}-${pad(hoje.getDate())}`;
        const lim = new Date(hoje); lim.setDate(lim.getDate()-30);
        const minStr  = `${lim.getFullYear()}-${pad(lim.getMonth()+1)}-${pad(lim.getDate())}`;
        campoData.min = minStr; campoData.max = hojeStr;
        campoData.addEventListener('change', function() {
            const ok = this.value >= minStr && this.value <= hojeStr;
            if (erroData) erroData.style.display = ok ? 'none' : 'block';
            if (!ok) this.value = '';
        });
    }

    // Aviso ao sair sem salvar (nova reunião)
    const formNova = document.getElementById('formSalvarNova');
    if (formNova) {
        let salvo = false;
        formNova.addEventListener('submit', function() { salvo = true; });
        window.addEventListener('beforeunload', function(e) {
            if (salvo) return;
            e.preventDefault();
            e.returnValue = 'A reunião ainda não foi salva. Se sair agora, os dados serão perdidos.';
        });
    }

    // Validação: todos os membros devem ter presença marcada
    const btnSalvarNova = document.getElementById('btnSalvarNova');
    if (btnSalvarNova && formNova) {
        formNova.addEventListener('submit', function(e) {
            const grupos = formNova.querySelectorAll('tbody tr');
            let todosM = true;
            grupos.forEach(function(tr) {
                const radios = tr.querySelectorAll('input[type="radio"]');
                const marcado = Array.from(radios).some(r => r.checked);
                if (!marcado) todosM = false;
            });
            if (!todosM) {
                e.preventDefault();
                alert('Marque a presença ou ausência de todos os membros antes de salvar.');
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
