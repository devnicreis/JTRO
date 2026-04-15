<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/index.php">← Voltar para início</a>
</div>

<div class="page-header">
    <h1>Diagnóstico de GFs</h1>
    <p class="page-header-subtitulo">Selecione um Grupo Familiar para visualizar o diagnóstico completo.</p>
</div>

<!-- Seletor de GF -->
<form method="GET" action="/diagnostico_gf.php" class="diag-selector-form">
    <div class="diag-selector-bar">
        <div class="campo" style="flex:1; margin:0;">
            <select id="grupo_id" name="grupo_id" onchange="this.form.submit()">
                <option value="">Selecione um Grupo Familiar</option>
                <?php foreach ($gruposAtivosLista as $g): ?>
                    <?php if ((int)($g['ativo'] ?? 1) !== 1) continue; ?>
                    <option value="<?php echo (int)$g['id']; ?>"
                        <?php echo $grupoSelecionadoId === (int)$g['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g['nome']); ?>
                        <?php if (!empty($g['lideres'])): ?>
                            — <?php echo htmlspecialchars($g['lideres']); ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($grupoSelecionadoId > 0): ?>
            <a class="botao-link botao-secundario" href="/diagnostico_gf.php">Limpar</a>
        <?php endif; ?>
    </div>
</form>

<?php if (!$grupoSelecionado): ?>
    <div class="presencas-card" style="text-align:center; padding: 48px;">
        <p style="color: var(--color-text-muted); font-size: 14px;">
            Escolha um GF acima para visualizar o diagnóstico.
        </p>
    </div>

<?php else: ?>

    <!-- Badge de diagnóstico + métricas -->
    <div style="margin-bottom: 20px;">
        <span class="diagnostico-faixa <?php echo htmlspecialchars($diagnosticoClasse); ?>">
            Diagnóstico: <?php echo htmlspecialchars($diagnostico); ?>
        </span>
    </div>

    <div class="cards-resumo diag-cards-resumo">
        <div class="card-resumo card-resumo-azul">
            <div class="card-resumo-label">Membros ativos</div>
            <div class="numero"><?php echo htmlspecialchars($grupoSelecionado['total_membros'] ?? '—'); ?></div>
        </div>
        <div class="card-resumo card-resumo-verde">
            <div class="card-resumo-label">Total de reuniões</div>
            <div class="numero"><?php echo htmlspecialchars($grupoSelecionado['total_reunioes'] ?? '—'); ?></div>
        </div>
        <div class="card-resumo card-resumo-roxo">
            <div class="card-resumo-label">Última reunião</div>
            <div class="numero" style="font-size:16px; padding-top:5px;">
                <?php echo !empty($grupoSelecionado['ultima_reuniao']) ? htmlspecialchars(formatarDataBr($grupoSelecionado['ultima_reuniao'])) : '—'; ?>
            </div>
        </div>
        <div class="card-resumo card-resumo-amber">
            <div class="card-resumo-label">Dia e horário</div>
            <div class="numero" style="font-size:15px; padding-top:5px;">
                <?php echo htmlspecialchars($grupoSelecionado['dia_semana']); ?>
                · <?php echo htmlspecialchars($grupoSelecionado['horario']); ?>
            </div>
        </div>
        <div class="card-resumo card-resumo-terracota">
            <div class="card-resumo-label">Líderes no GF</div>
            <div class="numero"><?php echo (int) $totalLideresNoGrupo; ?></div>
        </div>
        <div class="card-resumo card-resumo-tooltip card-resumo-violeta-suave" tabindex="0">
            <div class="card-resumo-label">Filhos (de 0 a 9 anos)</div>
            <div class="numero"><?php echo count($filhosDoGrupo); ?></div>
            <?php if (!empty($filhosDoGrupo)): ?>
                <div class="card-tooltip">
                    <?php foreach ($filhosDoGrupo as $filho): ?>
                        <div class="card-tooltip-item"><?php echo htmlspecialchars($filho['nome']); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra visual de presença -->
    <?php
    $presTotal  = (int) $resumoPresenca['total_presencas'];
    $ausTotal   = (int) $resumoPresenca['total_ausencias'];
    $pctPres    = (float) $resumoPresenca['percentual_presencas'];
    $pctAus     = (float) $resumoPresenca['percentual_ausencias'];
    ?>
    <div class="presencas-card diag-barra-card">
        <h2>Presença geral do grupo</h2>
        <div class="diag-barra-linha">
            <div class="barra-percentual" style="height: 14px; flex:1;">
                <div class="barra-presenca" style="width: <?php echo $pctPres; ?>%;"></div>
                <div class="barra-ausencia" style="width: <?php echo $pctAus; ?>%;"></div>
            </div>
        </div>
        <div class="diag-barra-legendas">
            <span class="legenda-presenca"><?php echo $pctPres; ?>% presença</span>
            <span style="color: var(--color-text-muted); font-size: 12px;"><?php echo $presTotal; ?> registros</span>
            <span class="legenda-ausencia" style="margin-left:auto;"><?php echo $pctAus; ?>% ausência</span>
            <span style="color: var(--color-text-muted); font-size: 12px;"><?php echo $ausTotal; ?> registros</span>
        </div>

        <?php if (!empty($faltosos)): ?>
            <div class="erro" style="margin-top: 16px;">
                <strong>Membros com faltas consecutivas:</strong><br>
                <?php foreach ($faltosos as $f): ?>
                    <?php echo htmlspecialchars($f['nome']); ?> — <?php echo (int)$f['faltas_consecutivas']; ?> faltas consecutivas<br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Grid: membros + últimas reuniões -->
    <div class="diag-grid-layout">

        <!-- Membros com filtro -->
        <div>
            <div class="presencas-card">
                <h2>
                    Membros do grupo
                    <?php if (!empty($resumoMembros)): ?>
                        <span style="font-size:11px; font-weight:400; color:var(--color-text-muted); text-transform:none; letter-spacing:0;">
                            <?php echo count($resumoMembros); ?> membros
                        </span>
                    <?php endif; ?>
                </h2>

                <div style="margin-bottom: 12px;">
                    <input
                        type="text"
                        id="filtroMembro"
                        placeholder="Filtrar por nome..."
                        oninput="filtrarMembros(this.value)"
                        style="width:100%; padding:7px 10px; border:1px solid var(--color-border-md); border-radius:var(--radius-md); font-size:13px;">
                </div>

                <?php if (empty($membrosFiltrados)): ?>
                    <p style="color:var(--color-text-muted); font-size:13px;">Nenhum membro registrado.</p>
                <?php else: ?>
                    <div class="diag-membros-wrapper">
                        <table id="tabelaMembros">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Líder</th>
                                    <th>Última presença</th>
                                    <th>Pres.</th>
                                    <th>Aus.</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($membrosFiltrados as $membro): ?>
                                    <?php
                                    $pct = (float) $membro['percentual_presenca'];
                                    $corPct = $pct >= 70 ? 'var(--color-green)' : ($pct >= 50 ? 'var(--color-amber)' : 'var(--color-red)');
                                    $largBarra = min(100, $pct);
                                    $temAlerta = !empty(array_filter($faltosos, fn($f) => $f['nome'] === $membro['nome']));
                                    ?>
                                    <tr class="membro-row">
                                        <td>
                                            <?php echo htmlspecialchars($membro['nome']); ?>
                                            <?php if ($temAlerta): ?>
                                                <span title="Faltas consecutivas" style="color:var(--color-amber); font-size:12px; margin-left:4px;">
                                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 2l5.5 10h-11z"/><path d="M8 7v2M8 11h.01"/></svg>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($membro['lideranca_label'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars(formatarDataBr($membro['ultima_presenca'] ?? null)); ?></td>
                                        <td style="text-align:center;"><?php echo htmlspecialchars($membro['total_presencas']); ?></td>
                                        <td style="text-align:center;"><?php echo htmlspecialchars($membro['total_ausencias']); ?></td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:6px;">
                                                <div style="width:44px; height:5px; background:#edeae4; border-radius:99px; overflow:hidden; flex-shrink:0;">
                                                    <div style="width:<?php echo $largBarra; ?>%; height:100%; background:<?php echo $corPct; ?>; border-radius:99px;"></div>
                                                </div>
                                                <span style="font-size:12px; font-weight:500; color:<?php echo $corPct; ?>;">
                                                    <?php echo $pct; ?>%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Últimas reuniões do GF -->
        <div>
            <div class="presencas-card quadro-ultimas-reunioes">
                <h2>Últimas reuniões</h2>
                <?php if (empty($ultimasReunioes)): ?>
                    <p style="color:var(--color-text-muted); font-size:13px;">Nenhuma reunião registrada.</p>
                <?php else: ?>
                    <div class="tabela-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Horário</th>
                                    <th>Pres.</th>
                                    <th>Aus.</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimasReunioes as $reuniao): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?></td>
                                        <td><?php echo htmlspecialchars($reuniao['horario']); ?></td>
                                        <td><?php echo htmlspecialchars($reuniao['total_presentes']); ?></td>
                                        <td><?php echo htmlspecialchars($reuniao['total_ausentes']); ?></td>
                                        <td>
                                            <a class="btn-visualizar" href="/reuniao_visualizar.php?id=<?php echo (int)$reuniao['id']; ?>">
                                                <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="3"/><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z"/></svg>
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

<?php endif; ?>

<script>
function filtrarMembros(termo) {
    const linhas = document.querySelectorAll('#tabelaMembros .membro-row');
    const t = termo.toLowerCase().trim();
    linhas.forEach(function(linha) {
        const nome = linha.querySelector('td').textContent.toLowerCase();
        linha.style.display = (!t || nome.includes(t)) ? '' : 'none';
    });
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
