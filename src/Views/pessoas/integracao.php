<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
if (!function_exists('renderIntegracaoIcon')) {
    function renderIntegracaoIcon(string $tipo): string
    {
        return match ($tipo) {
            'edit' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0 0-3L16.5 4a2.1 2.1 0 0 0-3 0L3 14.5V20h1Z" stroke="#475467" stroke-width="1.8" stroke-linejoin="round"/><path d="m13.5 6.5 4 4" stroke="#475467" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'delete' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M9 7V5.8A1.8 1.8 0 0 1 10.8 4h2.4A1.8 1.8 0 0 1 15 5.8V7m-7 0 .7 12a1.8 1.8 0 0 0 1.8 1.7h2.8a1.8 1.8 0 0 0 1.8-1.7L16 7M10 11.5v4.5M14 11.5v4.5" stroke="#475467" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'lock' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="#98a2b3" stroke-width="1.8" stroke-linecap="round"/><rect x="5" y="10" width="14" height="10" rx="2.2" stroke="#98a2b3" stroke-width="1.8"/><path d="M12 13.3v2.4" stroke="#98a2b3" stroke-width="1.8" stroke-linecap="round"/></svg>',
            default => '',
        };
    }
}

$acaoIntegracaoBotaoStyle = 'all:unset !important;box-sizing:border-box !important;width:34px !important;height:34px !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;flex:0 0 auto !important;padding:0 !important;margin:0 !important;border:1px solid #d0d5dd !important;border-radius:10px !important;background:#fff !important;color:#475467 !important;cursor:pointer !important;line-height:0 !important;box-shadow:none !important;appearance:none !important;-webkit-appearance:none !important;';
$acaoIntegracaoBotaoDeleteStyle = 'background:#fff !important;color:#475467 !important;border-color:#d0d5dd !important;';
$acaoIntegracaoLockStyle = 'all:unset !important;box-sizing:border-box !important;width:34px !important;height:34px !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;flex:0 0 auto !important;padding:0 !important;margin:0 !important;border:1px solid #d0d5dd !important;border-radius:10px !important;background:#f8fafc !important;color:#98a2b3 !important;line-height:0 !important;cursor:default !important;pointer-events:none !important;';
$acaoIntegracaoFormStyle = 'all:unset !important;box-sizing:border-box !important;display:inline-flex !important;margin:0 !important;padding:0 !important;border:0 !important;background:transparent !important;';
?>

<style>
.integracao-acoes {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    white-space: nowrap;
    min-height: 34px;
}

.integracao-acoes button.integracao-acao-btn,
.integracao-acoes button.integracao-acao-lock {
    all: unset;
    box-sizing: border-box;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #d0d5dd !important;
    border-radius: 10px;
    background: #fff !important;
    color: #475467 !important;
    flex: 0 0 auto;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease, color 0.15s ease;
    box-shadow: none !important;
    outline: none;
}

.integracao-acoes button.integracao-acao-btn:hover {
    background: #f2f4f7 !important;
    border-color: #cfd4dc !important;
    transform: translateY(-1px);
}

.integracao-acoes button.integracao-acao-btn svg,
.integracao-acoes button.integracao-acao-lock svg {
    width: 16px;
    height: 16px;
    display: block;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.integracao-acoes button.integracao-acao-btn--delete {
    color: #475467 !important;
}

.integracao-acoes button.integracao-acao-lock,
.integracao-acoes .integracao-acao-lock {
    cursor: default;
    color: #98a2b3 !important;
    background: #f8fafc !important;
}

.integracao-acoes button.integracao-acao-lock:hover,
.integracao-acoes .integracao-acao-lock:hover {
    transform: none;
    background: #f8fafc !important;
    border-color: #d0d5dd !important;
}

.integracao-acao-form {
    all: unset;
    box-sizing: border-box;
    display: inline-flex;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
}
</style>

<div class="menu">
    <a href="/index.php">&larr; Voltar para Início</a>
</div>

<div class="integracao-topbar">
    <div>
        <div class="page-header" style="margin-bottom:8px;">
            <h1>Aulas de Integração</h1>
        </div>
        <p>Acompanhe o progresso, ajuste apenas aulas manuais e mantenha o histórico visual limpo.</p>
    </div>

    <button
        type="button"
        class="btn-nova-aula-manual"
        id="abrirNovaAulaManual"
    >
        + Nova Aula Manual
    </button>
</div>

<?php if ($erro !== ''): ?>
    <div class="mensagem" style="margin-bottom:20px; background:#fdecea; color:#b42318;">
        <?php echo htmlspecialchars($erro); ?>
    </div>
<?php endif; ?>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem" style="margin-bottom:20px;">
        <?php echo htmlspecialchars($mensagem); ?>
    </div>
<?php endif; ?>

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

<div class="tabela-wrapper">
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Aula</th>
                <th>Status</th>
                <th>Data de realização</th>
                <th>Origem</th>
                <th style="width:120px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($progresso as $aula): ?>
                <?php
                $origem = (string) ($aula['origem'] ?? '');
                $origemLabel = match ($origem) {
                    'reuniao' => 'Reunião',
                    'retiro' => 'Retiro',
                    'manual' => 'Adicionado Manualmente',
                    default => 'Pendente',
                };

                $origemBadge = match ($origem) {
                    'reuniao' => 'badge-blue',
                    'retiro' => 'badge-green',
                    'manual' => 'badge-gray',
                    default => 'badge-gray',
                };

                $podeEditarManual = $origem === 'manual';
                ?>
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
                        <span class="badge <?php echo htmlspecialchars($origemBadge); ?>">
                            <?php echo htmlspecialchars($origemLabel); ?>
                        </span>
                    </td>
                    <td>
                        <div class="integracao-acoes">
                            <?php if ($podeEditarManual): ?>
                                <button
                                    type="button"
                                    class="integracao-acao-btn"
                                    style="<?php echo htmlspecialchars($acaoIntegracaoBotaoStyle); ?>"
                                    title="Editar aula manual"
                                    aria-label="Editar aula manual"
                                    data-action="abrir-modal"
                                    data-modo="editar"
                                    data-aula-codigo-original="<?php echo htmlspecialchars($aula['codigo']); ?>"
                                    data-aula-codigo="<?php echo htmlspecialchars($aula['codigo']); ?>"
                                    data-data-aula="<?php echo htmlspecialchars((string) ($aula['data_aula'] ?? '')); ?>"
                                >
                                    <?php echo renderIntegracaoIcon('edit'); ?>
                                </button>

                                <form class="integracao-acao-form" style="<?php echo htmlspecialchars($acaoIntegracaoFormStyle); ?>" method="POST" action="/pessoas_integracao.php?id=<?php echo (int) $pessoaId; ?>" onsubmit="return confirm('Remover esta aula manual?');">
                                    <?php echo Auth::csrfField(); ?>
                                    <input type="hidden" name="remover_aula_manual" value="1">
                                    <input type="hidden" name="aula_codigo" value="<?php echo htmlspecialchars($aula['codigo']); ?>">
                                    <button
                                        type="submit"
                                        class="integracao-acao-btn integracao-acao-btn--delete"
                                        style="<?php echo htmlspecialchars($acaoIntegracaoBotaoStyle . $acaoIntegracaoBotaoDeleteStyle); ?>"
                                        title="Excluir aula manual"
                                        aria-label="Excluir aula manual"
                                    >
                                        <?php echo renderIntegracaoIcon('delete'); ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="integracao-acao-lock" style="<?php echo htmlspecialchars($acaoIntegracaoLockStyle); ?>" title="Apenas aulas manuais podem ser alteradas" aria-label="Apenas aulas manuais podem ser alteradas">
                                    <?php echo renderIntegracaoIcon('lock'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="integracao-modal" id="modalAulaManual" hidden>
    <div class="integracao-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalAulaManualTitulo">
        <div class="integracao-modal-header">
            <div>
                <h3 id="modalAulaManualTitulo">Nova Aula Manual</h3>
            </div>
            <button type="button" class="integracao-modal-fechar" data-modal-close aria-label="Fechar modal">×</button>
        </div>

        <p class="integracao-modal-nota" id="modalAulaManualNota">
            A data informada será aplicada à próxima aula pendente ou à aula manual selecionada.
        </p>

        <form id="formAulaManual" method="POST" action="/pessoas_integracao.php?id=<?php echo (int) $pessoaId; ?>">
            <?php echo Auth::csrfField(); ?>
            <input type="hidden" name="salvar_aula_manual" value="1">
            <input type="hidden" name="aula_codigo_original" id="modalAulaCodigoOriginal" value="">

            <div class="integracao-modal-campo">
                <label for="modalDataAula">Data de realização</label>
                <input type="date" id="modalDataAula" name="data_aula" value="" required>
            </div>

            <div class="integracao-modal-campo" style="margin-top:12px;">
                <label for="modalAulaCodigoSelect">Aula ministrada</label>
                <select id="modalAulaCodigoSelect" name="aula_codigo" required>
                    <option value="">Selecione a aula</option>
                    <?php foreach ($aulasIntegracao as $codigo => $titulo): ?>
                        <option value="<?php echo htmlspecialchars($codigo); ?>">
                            <?php echo htmlspecialchars($codigo . ' — ' . $titulo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="integracao-modal-acoes">
                <button type="button" class="btn-gf btn-gf-desativar" data-modal-close>Cancelar</button>
                <button type="submit" class="btn-gf btn-gf-editar" id="modalSubmitAulaManual">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalAulaManual');
    const botaoAbrirNovo = document.getElementById('abrirNovaAulaManual');
    const modalTitulo = document.getElementById('modalAulaManualTitulo');
    const modalNota = document.getElementById('modalAulaManualNota');
    const modalAulaCodigoOriginal = document.getElementById('modalAulaCodigoOriginal');
    const modalAulaCodigoSelect = document.getElementById('modalAulaCodigoSelect');
    const modalData = document.getElementById('modalDataAula');
    const modalSubmit = document.getElementById('modalSubmitAulaManual');

    if (!modal || !botaoAbrirNovo || !modalTitulo || !modalNota || !modalAulaCodigoOriginal || !modalAulaCodigoSelect || !modalData || !modalSubmit) {
        return;
    }

    function abrirModal(configuracao) {
        const modo = configuracao.modo || 'novo';
        const codigoOriginal = configuracao.codigoOriginal || '';
        const codigo = configuracao.codigo || '';
        const data = configuracao.data || '';

        modal.hidden = false;
        document.body.style.overflow = 'hidden';

        modalTitulo.textContent = modo === 'editar' ? 'Editar Aula Manual' : 'Nova Aula Manual';
        modalNota.textContent = modo === 'editar'
            ? 'Atualize a data ou ajuste a aula manual registrada.'
            : 'Informe a data e selecione a aula ministrada antes de salvar.';
        modalAulaCodigoOriginal.value = codigoOriginal;
        modalAulaCodigoSelect.value = codigo;
        modalData.value = data;
        modalSubmit.textContent = modo === 'editar' ? 'Salvar alterações' : 'Adicionar aula';
    }

    function fecharModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    botaoAbrirNovo.addEventListener('click', function () {
        if (botaoAbrirNovo.disabled) {
            return;
        }

        abrirModal({
            modo: 'novo',
            codigoOriginal: '',
            codigo: '',
            data: '',
        });
    });

    document.querySelectorAll('[data-action="abrir-modal"]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            abrirModal({
                modo: botao.dataset.modo || 'editar',
                codigoOriginal: botao.dataset.aulaCodigoOriginal || '',
                codigo: botao.dataset.aulaCodigo || '',
                data: botao.dataset.dataAula || '',
            });
        });
    });

    modal.querySelectorAll('[data-modal-close]').forEach(function (botaoFechar) {
        botaoFechar.addEventListener('click', fecharModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            fecharModal();
        }
    });

    window.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            fecharModal();
        }
    });

    <?php if ($abrirModal): ?>
    abrirModal({
        modo: <?php echo json_encode($modalModo); ?>,
        codigoOriginal: <?php echo json_encode($modalAulaCodigoOriginal); ?>,
        codigo: <?php echo json_encode($modalAulaCodigo); ?>,
        data: <?php echo json_encode($modalDataAula); ?>,
    });
    <?php endif; ?>
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
