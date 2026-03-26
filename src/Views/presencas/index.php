<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php $aulasIntegracao = aulasIntegracao(); ?>

<div class="page-header">
    <h1>Reuniões e Presenças</h1>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>
<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="GET" action="/presencas.php" id="formCarregar">
    <div class="grid">
        <div class="campo">
            <label for="grupo_id">Grupo Familiar</label>
            <select id="grupo_id" name="grupo_id" required>
                <option value="">Selecione</option>
                <?php foreach ($grupos as $grupo): ?>
                    <option value="<?php echo $grupo['id']; ?>" <?php echo $grupoId === (int) $grupo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($grupo['nome']); ?> — <?php echo htmlspecialchars($grupo['dia_semana']); ?> às <?php echo htmlspecialchars($grupo['horario']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="data">Data da reunião</label>
            <input type="date" id="data" name="data" required value="<?php echo htmlspecialchars($data); ?>">
            <div id="erro-data" class="erro" style="display:none; margin-top:8px;">
                A reunião só pode ser criada para hoje ou até 30 dias atrás.
            </div>
        </div>
    </div>
    <button type="submit">Carregar reunião</button>
</form>

<?php if ($modoNovaReuniao && !empty($membrosGrupo)): ?>
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

            <?php if (($resumoGrupoHorario['perfil_grupo'] ?? '') === 'integracao'): ?>
                <div class="campo">
                    <label for="aula_integracao_codigo">Aula da integração</label>
                    <select id="aula_integracao_codigo" name="aula_integracao_codigo" required>
                        <option value="">Selecione</option>
                        <?php foreach ($aulasIntegracao as $codigo => $titulo): ?>
                            <option value="<?php echo htmlspecialchars($codigo); ?>" <?php echo (($_POST['aula_integracao_codigo'] ?? '') === $codigo) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($codigo . ' - ' . $titulo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="campo">
                <label for="observacoes">Observações <span style="font-weight:400; color:var(--color-text-muted);">(opcional)</span></label>
                <textarea id="observacoes" name="observacoes" maxlength="255" style="min-height:70px;"><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
            </div>

            <div class="tabela-wrapper" style="margin-top:4px; margin-bottom:20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Presença</th>
                            <th>Detalhe obrigatório</th>
                            <th>Justificativa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membrosGrupo as $membro): ?>
                            <?php $prefixo = 'presencas[' . $membro['id'] . ']'; ?>
                            <tr class="linha-presenca" data-linha-id="<?php echo $membro['id']; ?>">
                                <td><?php echo htmlspecialchars($membro['nome']); ?></td>
                                <td>
                                    <div class="presenca-switch-wrap">
                                        <input type="radio" id="pres_<?php echo $membro['id']; ?>" name="<?php echo $prefixo; ?>[status]" value="presente" class="presenca-radio"
                                               <?php echo (($_POST['presencas'][$membro['id']]['status'] ?? '') === 'presente') ? 'checked' : ''; ?>>
                                        <label for="pres_<?php echo $membro['id']; ?>" class="presenca-toggle presenca-toggle-pres">Presente</label>

                                        <input type="radio" id="aus_<?php echo $membro['id']; ?>" name="<?php echo $prefixo; ?>[status]" value="ausente" class="presenca-radio"
                                               <?php echo (($_POST['presencas'][$membro['id']]['status'] ?? '') === 'ausente') ? 'checked' : ''; ?>>
                                        <label for="aus_<?php echo $membro['id']; ?>" class="presenca-toggle presenca-toggle-aus">Ausente</label>
                                    </div>
                                </td>
                                <td>
                                    <div class="campo-detalhe-presenca">
                                        <select name="<?php echo $prefixo; ?>[presente_tempo]" class="select-presente">
                                            <option value="">No horário / Atrasado</option>
                                            <option value="no_horario" <?php echo (($_POST['presencas'][$membro['id']]['presente_tempo'] ?? '') === 'no_horario') ? 'selected' : ''; ?>>No horário</option>
                                            <option value="atrasado" <?php echo (($_POST['presencas'][$membro['id']]['presente_tempo'] ?? '') === 'atrasado') ? 'selected' : ''; ?>>Atrasado</option>
                                        </select>
                                        <select name="<?php echo $prefixo; ?>[ausencia_tipo]" class="select-ausencia">
                                            <option value="">Justificada / Injustificada</option>
                                            <option value="justificada" <?php echo (($_POST['presencas'][$membro['id']]['ausencia_tipo'] ?? '') === 'justificada') ? 'selected' : ''; ?>>Justificada</option>
                                            <option value="injustificada" <?php echo (($_POST['presencas'][$membro['id']]['ausencia_tipo'] ?? '') === 'injustificada') ? 'selected' : ''; ?>>Injustificada</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" maxlength="50" class="input-justificativa-atraso"
                                           name="<?php echo $prefixo; ?>[justificativa_atraso]"
                                           placeholder="Resuma a justificativa do atraso, se houver"
                                           value="<?php echo htmlspecialchars($_POST['presencas'][$membro['id']]['justificativa_atraso'] ?? ''); ?>">
                                    <input type="text" maxlength="50" class="input-justificativa-ausencia"
                                           name="<?php echo $prefixo; ?>[justificativa_ausencia]"
                                           placeholder="Resuma a justificativa da ausência"
                                           value="<?php echo htmlspecialchars($_POST['presencas'][$membro['id']]['justificativa_ausencia'] ?? ''); ?>">
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
    <div class="presencas-card" style="margin-top:24px;">
        <h2 style="margin-bottom:20px;">
            <?php echo htmlspecialchars($reuniao['grupo_nome']); ?>
            <span style="color:var(--color-text-muted); font-weight:400;"> — </span>
            <?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?>
        </h2>

        <form method="POST" action="/presencas.php" id="formEditarReuniao">
            <input type="hidden" name="salvar_presencas" value="1">
            <input type="hidden" name="reuniao_id" value="<?php echo $reuniao['id']; ?>">
            <input type="hidden" name="grupo_id" value="<?php echo $grupoId; ?>">
            <input type="hidden" name="data" value="<?php echo htmlspecialchars($data); ?>">

            <div class="grid" style="margin-bottom:0;">
                <div class="campo">
                    <label for="local">Local</label>
                    <input type="text" id="local" name="local" maxlength="25" value="<?php echo htmlspecialchars($reuniao['local'] ?? ''); ?>">
                </div>
                <div class="campo">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" maxlength="255" style="min-height:70px;"><?php echo htmlspecialchars($reuniao['observacoes'] ?? ''); ?></textarea>
                </div>
            </div>

            <?php if (($reuniao['perfil_grupo'] ?? '') === 'integracao'): ?>
                <div class="campo">
                    <label for="aula_integracao_codigo">Aula da integração</label>
                    <select id="aula_integracao_codigo" name="aula_integracao_codigo" required>
                        <option value="">Selecione</option>
                        <?php foreach ($aulasIntegracao as $codigo => $titulo): ?>
                            <option value="<?php echo htmlspecialchars($codigo); ?>" <?php echo (($reuniao['aula_integracao_codigo'] ?? '') === $codigo) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($codigo . ' - ' . $titulo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="tabela-wrapper" style="margin-top:16px; margin-bottom:20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Presença</th>
                            <th>Detalhe obrigatório</th>
                            <th>Justificativa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaPresencas as $presenca): ?>
                            <?php $prefixo = 'presencas[' . $presenca['id'] . ']'; ?>
                            <tr class="linha-presenca" data-linha-id="<?php echo $presenca['id']; ?>">
                                <td><?php echo htmlspecialchars($presenca['nome']); ?></td>
                                <td>
                                    <div class="presenca-switch-wrap">
                                        <input type="radio" id="pres_<?php echo $presenca['id']; ?>" name="<?php echo $prefixo; ?>[status]" value="presente" class="presenca-radio"
                                               <?php echo ($presenca['status'] === 'presente') ? 'checked' : ''; ?>>
                                        <label for="pres_<?php echo $presenca['id']; ?>" class="presenca-toggle presenca-toggle-pres">Presente</label>

                                        <input type="radio" id="aus_<?php echo $presenca['id']; ?>" name="<?php echo $prefixo; ?>[status]" value="ausente" class="presenca-radio"
                                               <?php echo ($presenca['status'] === 'ausente') ? 'checked' : ''; ?>>
                                        <label for="aus_<?php echo $presenca['id']; ?>" class="presenca-toggle presenca-toggle-aus">Ausente</label>
                                    </div>
                                </td>
                                <td>
                                    <div class="campo-detalhe-presenca">
                                        <select name="<?php echo $prefixo; ?>[presente_tempo]" class="select-presente">
                                            <option value="">No horário / Atrasado</option>
                                            <option value="no_horario" <?php echo (($presenca['presente_tempo'] ?? '') === 'no_horario') ? 'selected' : ''; ?>>No horário</option>
                                            <option value="atrasado" <?php echo (($presenca['presente_tempo'] ?? '') === 'atrasado') ? 'selected' : ''; ?>>Atrasado</option>
                                        </select>
                                        <select name="<?php echo $prefixo; ?>[ausencia_tipo]" class="select-ausencia">
                                            <option value="">Justificada / Injustificada</option>
                                            <option value="justificada" <?php echo (($presenca['ausencia_tipo'] ?? '') === 'justificada') ? 'selected' : ''; ?>>Justificada</option>
                                            <option value="injustificada" <?php echo (($presenca['ausencia_tipo'] ?? '') === 'injustificada') ? 'selected' : ''; ?>>Injustificada</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" maxlength="50" class="input-justificativa-atraso"
                                           name="<?php echo $prefixo; ?>[justificativa_atraso]"
                                           placeholder="Resuma a justificativa do atraso, se houver"
                                           value="<?php echo htmlspecialchars($presenca['justificativa_atraso'] ?? ''); ?>">
                                    <input type="text" maxlength="50" class="input-justificativa-ausencia"
                                           name="<?php echo $prefixo; ?>[justificativa_ausencia]"
                                           placeholder="Resuma a justificativa da ausência"
                                           value="<?php echo htmlspecialchars($presenca['justificativa_ausencia'] ?? ''); ?>">
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
                    <a class="btn-presenca-oracao" href="/pedidos_oracao.php?reuniao_id=<?php echo (int) $reuniao['id']; ?>">
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
    const campoData = document.getElementById('data');
    const erroData = document.getElementById('erro-data');

    if (campoData) {
        const pad = n => String(n).padStart(2, '0');
        const hoje = new Date();
        const hojeStr = `${hoje.getFullYear()}-${pad(hoje.getMonth() + 1)}-${pad(hoje.getDate())}`;
        const lim = new Date(hoje);
        lim.setDate(lim.getDate() - 30);
        const minStr = `${lim.getFullYear()}-${pad(lim.getMonth() + 1)}-${pad(lim.getDate())}`;
        campoData.min = minStr;
        campoData.max = hojeStr;
        campoData.addEventListener('change', function() {
            const ok = this.value >= minStr && this.value <= hojeStr;
            if (erroData) erroData.style.display = ok ? 'none' : 'block';
            if (!ok) this.value = '';
        });
    }

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

    function sincronizarLinha(tr) {
        const status = tr.querySelector('input[type="radio"][value="presente"]:checked')
            ? 'presente'
            : (tr.querySelector('input[type="radio"][value="ausente"]:checked') ? 'ausente' : '');
        const selectPresente = tr.querySelector('.select-presente');
        const selectAusencia = tr.querySelector('.select-ausencia');
        const inputAtraso = tr.querySelector('.input-justificativa-atraso');
        const inputAusencia = tr.querySelector('.input-justificativa-ausencia');

        const mostrarPresente = status === 'presente';
        const mostrarAusencia = status === 'ausente';

        selectPresente.style.display = mostrarPresente ? '' : 'none';
        selectAusencia.style.display = mostrarAusencia ? '' : 'none';
        inputAtraso.style.display = mostrarPresente && selectPresente.value === 'atrasado' ? '' : 'none';
        inputAusencia.style.display = mostrarAusencia && selectAusencia.value === 'justificada' ? '' : 'none';

        selectPresente.required = mostrarPresente;
        selectAusencia.required = mostrarAusencia;
        inputAusencia.required = mostrarAusencia && selectAusencia.value === 'justificada';

        if (!mostrarPresente) {
            selectPresente.value = '';
            inputAtraso.value = '';
        }
        if (!mostrarAusencia) {
            selectAusencia.value = '';
            inputAusencia.value = '';
        }
        if (mostrarPresente && selectPresente.value !== 'atrasado') {
            inputAtraso.value = '';
        }
        if (mostrarAusencia && selectAusencia.value !== 'justificada') {
            inputAusencia.value = '';
        }
    }

    document.querySelectorAll('.linha-presenca').forEach(function(tr) {
        sincronizarLinha(tr);
        tr.querySelectorAll('input[type="radio"], select').forEach(function(el) {
            el.addEventListener('change', function() {
                sincronizarLinha(tr);
            });
        });
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
