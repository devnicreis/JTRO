<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
    <div>
        <h1>Agenda</h1>
        <p class="page-header-subtitulo">Atividades e eventos da Comunhão Cristã Abba FRG</p>
    </div>
    <?php if (Auth::isAdmin()): ?>
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <a href="/agenda_criar.php" class="botao-link" style="min-height:36px; font-size:13px;">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="margin-right:5px;"><path d="M8 2v12M2 8h12"/></svg>
                Novo evento
            </a>
            <button type="button" class="botao-link botao-secundario"
                    onclick="document.getElementById('modal-ics').style.display='flex'"
                    style="min-height:36px; font-size:13px;">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:5px;"><path d="M8 10V3M5 7l3 3 3-3"/><rect x="2" y="11" width="12" height="3" rx="1"/></svg>
                Importar .ics
            </button>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['criado'])): ?><div class="mensagem">Evento criado com sucesso.</div><?php endif; ?>
<?php if (isset($_GET['editado'])): ?><div class="mensagem">Evento atualizado com sucesso.</div><?php endif; ?>
<?php if ($mensagem !== ''): ?><div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>
<?php if ($erro !== ''): ?><div class="erro"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

<div class="agenda-wrap">

    <!-- ── Coluna esquerda: mini calendário + filtros ── -->
    <div class="agenda-sidebar-cal">

        <!-- Mini calendário -->
        <div class="agenda-mini-cal presencas-card" style="padding:14px 16px; margin-bottom:16px;">
            <?php
            $nomeMeses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
            $diasSemana = ['D','S','T','Q','Q','S','S'];
            $primeiroDia = mktime(0,0,0,$mes,1,$ano);
            $totalDias   = (int)date('t', $primeiroDia);
            $inicioSem   = (int)date('w', $primeiroDia); // 0=Dom
            $mesAnterior = $mes === 1 ? ['ano' => $ano-1, 'mes' => 12] : ['ano' => $ano, 'mes' => $mes-1];
            $mesSeguinte = $mes === 12 ? ['ano' => $ano+1, 'mes' => 1] : ['ano' => $ano, 'mes' => $mes+1];
            $hojeStr     = date('Y-m-d');
            $diasComEvSet = array_flip($diasComEventos);
            $dptoQs = $dpto ? '&dpto=' . urlencode($dpto) : '';
            ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <a href="/agenda.php?ano=<?php echo $mesAnterior['ano']; ?>&mes=<?php echo $mesAnterior['mes']; ?><?php echo $dptoQs; ?>"
                   style="color:var(--color-text-muted); text-decoration:none; font-size:16px; padding:2px 6px;">‹</a>
                <span style="font-size:13px; font-weight:500; color:var(--color-text-primary);">
                    <?php echo $nomeMeses[$mes-1] . ' ' . $ano; ?>
                </span>
                <a href="/agenda.php?ano=<?php echo $mesSeguinte['ano']; ?>&mes=<?php echo $mesSeguinte['mes']; ?><?php echo $dptoQs; ?>"
                   style="color:var(--color-text-muted); text-decoration:none; font-size:16px; padding:2px 6px;">›</a>
            </div>

            <div class="agenda-mini-grid">
                <?php foreach ($diasSemana as $d): ?>
                    <div class="agenda-mini-dow"><?php echo $d; ?></div>
                <?php endforeach; ?>

                <?php for ($i = 0; $i < $inicioSem; $i++): ?>
                    <div class="agenda-mini-dia agenda-mini-outro"></div>
                <?php endfor; ?>

                <?php for ($d = 1; $d <= $totalDias; $d++):
                    $dataStr = sprintf('%04d-%02d-%02d', $ano, $mes, $d);
                    $cls = 'agenda-mini-dia';
                    if ($dataStr === $hojeStr)        $cls .= ' agenda-mini-hoje';
                    if ($dataStr === $diaSelecionado) $cls .= ' agenda-mini-selecionado';
                    $temEvento = isset($diasComEvSet[$dataStr]);
                    $url = "/agenda.php?ano={$ano}&mes={$mes}&dia={$dataStr}{$dptoQs}";
                ?>
                    <a href="<?php echo $url; ?>" class="<?php echo $cls; ?>">
                        <?php echo $d; ?>
                        <?php if ($temEvento): ?><span class="agenda-mini-ponto"></span><?php endif; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Filtro por departamento -->
        <div class="presencas-card" style="padding:14px 16px;">
            <div style="font-size:11px; font-weight:500; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px;">Departamentos</div>
            <?php
            $todos = array_merge(['Todos'], AgendaRepository::DEPARTAMENTOS);
            foreach ($todos as $d):
                $ativo = ($dpto === '' && $d === 'Todos') || $dpto === $d;
                $dptoParam = $d === 'Todos' ? '' : urlencode($d);
                $url = "/agenda.php?ano={$ano}&mes={$mes}&dia=" . urlencode($diaSelecionado) . "&dpto={$dptoParam}";
                $cor = $ativo ? 'var(--color-blue)' : 'var(--color-text-secondary)';
            ?>
                <a href="<?php echo $url; ?>"
                   style="display:flex; align-items:center; gap:7px; padding:5px 6px; border-radius:var(--radius-md); font-size:12px; color:<?php echo $cor; ?>; text-decoration:none; font-weight:<?php echo $ativo ? '500' : '400'; ?>; background:<?php echo $ativo ? 'var(--color-blue-light)' : 'transparent'; ?>; margin-bottom:2px;">
                    <span style="width:7px; height:7px; border-radius:50%; background:<?php echo $ativo ? 'var(--color-blue)' : 'var(--color-border-md)'; ?>; flex-shrink:0;"></span>
                    <?php echo htmlspecialchars($d); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Coluna direita: eventos do dia ── -->
    <div class="agenda-lista-wrap">
        <div class="presencas-card">
            <?php
            $ts = strtotime($diaSelecionado);
            $diasPt = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
            $diaSemPt = $diasPt[(int)date('w', $ts)];
            $dataPt = date('j', $ts) . ' de ' . ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'][(int)date('n',$ts)-1] . ' de ' . date('Y',$ts);
            ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
                <div>
                    <div style="font-size:15px; font-weight:500; color:var(--color-text-primary);"><?php echo $diaSemPt; ?></div>
                    <div style="font-size:13px; color:var(--color-text-muted);"><?php echo $dataPt; ?></div>
                </div>
                <?php if (Auth::isAdmin()): ?>
                    <a href="/agenda_criar.php?data=<?php echo urlencode($diaSelecionado); ?>"
                       class="btn-visualizar" style="color:var(--color-blue); border-color:var(--color-blue-mid);">
                        + Evento neste dia
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($eventosDia)): ?>
                <p style="color:var(--color-text-muted); font-size:13px; text-align:center; padding:24px 0;">
                    Nenhum evento neste dia.
                </p>
            <?php else: ?>
                <div class="agenda-lista-eventos">
                    <?php foreach ($eventosDia as $ev):
                        $cor = agendaCorDpto($ev['departamento']);
                    ?>
                        <div class="agenda-evento-row">
                            <div class="agenda-evento-hora">
                                <?php echo htmlspecialchars(substr($ev['horario'], 0, 5)); ?>
                                <?php if ($ev['horario_fim']): ?>
                                    <span style="color:var(--color-text-muted); font-size:11px; display:block;">
                                        até <?php echo htmlspecialchars(substr($ev['horario_fim'], 0, 5)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="agenda-evento-barra" style="background:<?php echo $cor; ?>;"></div>
                            <div class="agenda-evento-info">
                                <div class="agenda-evento-titulo"><?php echo htmlspecialchars($ev['titulo']); ?></div>
                                <div style="margin-top:3px;">
                                    <span class="agenda-badge" style="background:<?php echo $cor; ?>22; color:<?php echo $cor; ?>;">
                                        <?php echo htmlspecialchars($ev['departamento']); ?>
                                    </span>
                                </div>
                                <?php if (!empty($ev['descricao'])): ?>
                                    <div style="font-size:12px; color:var(--color-text-muted); margin-top:5px; line-height:1.5;">
                                        <?php echo nl2br(htmlspecialchars($ev['descricao'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (Auth::isAdmin()): ?>
                                <div style="display:flex; flex-direction:column; gap:4px; flex-shrink:0;">
                                    <a href="/agenda_editar.php?id=<?php echo $ev['id']; ?>" class="btn-visualizar" style="font-size:11px; padding:3px 9px;">Editar</a>
                                    <form method="POST" action="/agenda.php" class="form-acao"
                                          onsubmit="return confirm('Excluir este evento?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?php echo $ev['id']; ?>">
                                        <button type="submit" style="font-size:11px; padding:3px 9px; background:var(--color-red-light); color:var(--color-red); border:1px solid #f7c1c1; border-radius:var(--radius-md); cursor:pointer; min-height:unset; width:100%;">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (Auth::isAdmin()): ?>
<!-- Modal importar ICS -->
<div id="modal-ics" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:400; align-items:center; justify-content:center;">
    <div style="background:var(--color-surface); border-radius:var(--radius-lg); padding:28px 32px; max-width:440px; width:100%; margin:20px; box-shadow:0 8px 32px rgba(0,0,0,0.15);">
        <h2 style="margin-bottom:6px;">Importar Google Calendar</h2>
        <p style="font-size:13px; color:var(--color-text-muted); margin-bottom:20px;">
            No Google Calendar: Configurações → selecione o calendário → Exportar. Um arquivo <strong>.ics</strong> será baixado. Faça o upload aqui.
        </p>
        <form method="POST" action="/agenda.php" enctype="multipart/form-data">
            <div class="campo">
                <label>Arquivo .ics</label>
                <input type="file" name="ics" accept=".ics" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:8px;">
                <button type="submit" class="btn-presenca-salvar" style="flex:1;">Importar</button>
                <button type="button" onclick="document.getElementById('modal-ics').style.display='none'"
                        class="botao-link botao-secundario" style="flex:1;">Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
function agendaCorDpto(string $dpto): string {
    $cores = [
        'Evento Geral '  => '#185FA5',
        'Pastoral'       => '#185FA5',
        'Evangelização'  => '#860d0d',
        'Abba Jovem'     => '#534AB7',
        'Abba Teen'      => '#0b851b',
        'Mulheres'       => '#993556',
        'EBI'            => '#993C1D',
        'Artes'          => '#534AB7',
        'Dança'          => '#D4537E',
        'Música'         => '#3B6D11',
        'Backstage'      => '#5F5E5A',
        'Comunicação'    => '#185FA5',
        'Atividades Semanais' => '#5F5E5A',
    ];
    return $cores[$dpto] ?? '#185FA5';
}
?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
