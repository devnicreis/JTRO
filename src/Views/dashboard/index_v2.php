<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<?php
$diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
$meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$diaSemana = $diasSemana[(int) date('w')];
$dia = date('j');
$mes = $meses[(int) date('n') - 1];
$ano = date('Y');
$dataFormatada = "$diaSemana, $dia de $mes de $ano";
$primeiroNome = htmlspecialchars(explode(' ', trim($usuario['nome']))[0]);

if (!function_exists('renderDashboardGruposFamiliaresV2')) {
    function renderDashboardGruposFamiliaresV2(array $gruposDoLider): void
    {
        ?>
        <div class="dashboard-grupos-scroll">
            <?php foreach ($gruposDoLider as $grupo): ?>
                <?php
                $presencas = (int) ($grupo['resumo_presenca']['total_presencas'] ?? 0);
                $ausencias = (int) ($grupo['resumo_presenca']['total_ausencias'] ?? 0);
                $percentualPresenca = (float) ($grupo['resumo_presenca']['percentual_presencas'] ?? 0);
                $percentualAusencia = (float) ($grupo['resumo_presenca']['percentual_ausencias'] ?? 0);
                $domingos = [1 => '1º Domingo', 2 => '2º Domingo', 3 => '3º Domingo', 4 => '4º Domingo', 5 => '5º Domingo'];
                ?>
                <div class="card-perfil">
                    <h3><?php echo htmlspecialchars($grupo['nome']); ?></h3>

                    <div class="grupo-meta-badges">
                        <span class="grupo-meta-badge"><strong>📅 Dia:</strong> <?php echo htmlspecialchars($grupo['dia_semana']); ?></span>
                        <span class="grupo-meta-badge"><strong>⏰ Horário:</strong> <?php echo htmlspecialchars($grupo['horario']); ?></span>
                        <span class="grupo-meta-badge"><strong>😇 Membros:</strong> <?php echo htmlspecialchars($grupo['total_membros_ativos']); ?></span>
                        <span class="grupo-meta-badge"><strong>💬 Reuniões:</strong> <?php echo htmlspecialchars($grupo['total_reunioes']); ?></span>
                        <span class="grupo-meta-badge"><strong>⏮ Última reunião:</strong> <?php echo !empty($grupo['ultima_reuniao']) ? htmlspecialchars(formatarDataBr($grupo['ultima_reuniao'])) : '—'; ?></span>
                    </div>

                    <?php if (!empty($grupo['item_celeiro']) || !empty($grupo['domingo_oracao_culto']) || !empty($grupo['proxima_cantina_data'])): ?>
                        <div class="escala-badges">
                            <?php if (!empty($grupo['item_celeiro'])): ?>
                                <span class="escala-badge escala-badge-celeiro">🌾 Celeiro: <?php echo htmlspecialchars($grupo['item_celeiro']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($grupo['domingo_oracao_culto'])): ?>
                                <span class="escala-badge escala-badge-oracao">🛐 Escala de Oração: <?php echo htmlspecialchars($domingos[(int) $grupo['domingo_oracao_culto']] ?? '—'); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($grupo['proxima_cantina_data'])): ?>
                                <span class="escala-badge escala-badge-cantina">🌭 Próxima Cantina: <?php echo htmlspecialchars(formatarDataBr($grupo['proxima_cantina_data'])); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="barra-percentual">
                        <div class="barra-presenca" style="width: <?php echo $percentualPresenca; ?>%;"></div>
                        <div class="barra-ausencia" style="width: <?php echo $percentualAusencia; ?>%;"></div>
                    </div>
                    <div class="resumo-percentual">
                        <span class="legenda-presenca">Presença: <?php echo $percentualPresenca; ?>%</span>
                        <span class="legenda-ausencia">Ausência: <?php echo $percentualAusencia; ?>%</span>
                        <span style="color: var(--color-text-muted); font-size: 12px;"><?php echo $presencas; ?> · <?php echo $ausencias; ?></span>
                    </div>

                    <?php if (!empty($grupo['faltosos'])): ?>
                        <div class="erro" style="margin-top: 12px;">
                            <strong>Membros com faltas consecutivas:</strong><br>
                            <?php foreach ($grupo['faltosos'] as $faltoso): ?>
                                <?php echo htmlspecialchars($faltoso['nome']); ?> — <?php echo htmlspecialchars($faltoso['faltas_consecutivas']); ?> faltas<br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($grupo['resumo_membros'])): ?>
                        <div class="tabela-wrapper" style="margin-top: 16px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Membro</th>
                                        <th>Última presença</th>
                                        <th>Pres.</th>
                                        <th>Aus.</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grupo['resumo_membros'] as $membro): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($membro['nome']); ?></td>
                                            <td><?php echo htmlspecialchars(formatarDataBr($membro['ultima_presenca'] ?? null)); ?></td>
                                            <td><?php echo htmlspecialchars($membro['total_presencas']); ?></td>
                                            <td><?php echo htmlspecialchars($membro['total_ausencias']); ?></td>
                                            <td><?php echo htmlspecialchars($membro['percentual_presenca']); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

$temAlertas = !empty($gruposAlarmantesAvisos) || !empty($membrosFaltososAvisos);
?>

<div class="dash-topbar">
    <div class="dash-topbar-esq">
        <h1 class="dash-titulo">Visão geral</h1>
        <p class="dash-data"><?php echo $dataFormatada; ?></p>
        <p class="dash-saudacao">Olá, <?php echo $primeiroNome; ?>!</p>
    </div>

    <div class="notif-wrap" id="notifWrap">
        <button class="notif-btn" id="notifBtn" type="button" aria-label="Avisos">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M8 1a5 5 0 015 5c0 3 1 4 1.5 5h-13C2 10 3 9 3 6a5 5 0 015-5z" />
                <path d="M6.5 13a1.5 1.5 0 003 0" />
            </svg>
            <?php if ($totalAvisos > 0): ?>
                <span class="notif-badge" id="notifBadge"><?php echo $totalAvisos; ?></span>
            <?php else: ?>
                <span class="notif-badge notif-badge-oculto" id="notifBadge"></span>
            <?php endif; ?>
        </button>

        <div class="notif-dropdown" id="notifDropdown" aria-hidden="true">
            <div class="notif-header">
                <span class="notif-header-titulo">Avisos</span>
                <button class="notif-marcar-todos" id="notifMarcarTodos" type="button">Marcar todos como lidos</button>
            </div>
            <div class="notif-tabs">
                <button class="notif-tab ativo" data-tab="nao-lidos" type="button">
                    Não lidos <span id="notifCountNaoLidos">(<?php echo $totalAvisos; ?>)</span>
                </button>
                <button class="notif-tab" data-tab="lidos" type="button">Lidos</button>
            </div>
            <div class="notif-list" id="notifList">
                <div class="notif-carregando">Carregando avisos...</div>
            </div>
        </div>
    </div>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($dashboardTipo === 'admin'): ?>
    <div class="cards-resumo">
        <div class="card-resumo card-resumo-tooltip">
            <div class="card-resumo-label"><div class="card-resumo-dot" style="background:#378ADD;"></div>Pessoas ativas</div>
            <div class="numero"><?php echo $totalPessoasAtivas; ?></div>
            <?php if (!empty($listaPessoasAtivas)): ?>
                <div class="card-tooltip">
                    <?php foreach ($listaPessoasAtivas as $nome): ?>
                        <div class="card-tooltip-item"><?php echo htmlspecialchars($nome); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-resumo card-resumo-tooltip">
            <div class="card-resumo-label"><div class="card-resumo-dot" style="background:#1D9E75;"></div>Líderes ativos</div>
            <div class="numero"><?php echo $totalLideresAtivos; ?></div>
            <?php if (!empty($listaLideresAtivos)): ?>
                <div class="card-tooltip">
                    <?php foreach ($listaLideresAtivos as $nome): ?>
                        <div class="card-tooltip-item"><?php echo htmlspecialchars($nome); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-resumo card-resumo-tooltip">
            <div class="card-resumo-label"><div class="card-resumo-dot" style="background:#7F77DD;"></div>GFs ativos</div>
            <div class="numero"><?php echo $totalGruposAtivos; ?></div>
            <?php if (!empty($listaGruposAtivos)): ?>
                <div class="card-tooltip">
                    <?php foreach ($listaGruposAtivos as $nome): ?>
                        <div class="card-tooltip-item"><?php echo htmlspecialchars($nome); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-resumo">
            <div class="card-resumo-label"><div class="card-resumo-dot" style="background:#EF9F27;"></div>Reuniões registradas</div>
            <div class="numero"><?php echo $totalReunioes; ?></div>
        </div>
        <div class="card-resumo">
            <div class="card-resumo-label"><div class="card-resumo-dot" style="background:#0F6E56;"></div>Presenças atualizadas</div>
            <div class="numero"><?php echo $totalPresencasAtualizadas; ?></div>
        </div>
    </div>

    <div class="dashboard-admin-top">
        <div class="presencas-card">
            <h2>Atenção agora</h2>
            <?php if (!$temAlertas): ?>
                <p style="color: var(--color-text-muted); font-size: 13px;">Nenhum alerta no momento.</p>
            <?php else: ?>
                <div class="dashboard-lista-limitada">
                    <?php foreach ($gruposAlarmantesAvisos as $grupoAviso): ?>
                        <div class="dash-alerta dash-alerta-danger">GF <?php echo htmlspecialchars($grupoAviso['nome']); ?> — presença alarmante</div>
                    <?php endforeach; ?>
                    <?php foreach ($membrosFaltososAvisos as $membro): ?>
                        <div class="dash-alerta dash-alerta-warn"><?php echo htmlspecialchars($membro['nome']); ?> — <?php echo (int) $membro['faltas_consecutivas']; ?> faltas consecutivas</div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($proximasReunioes ?? [])): ?>
            <div class="presencas-card">
                <h2>Próximas reuniões</h2>
                <div class="dashboard-lista-limitada">
                    <?php foreach ($proximasReunioes as $pr): ?>
                        <div class="dash-proxima-reuniao">
                            <div>
                                <div class="dash-proxima-gf"><?php echo htmlspecialchars($pr['nome']); ?></div>
                                <div class="dash-proxima-info">
                                    Líder: <?php echo htmlspecialchars($pr['lideres'] ?? '—'); ?>
                                    · <?php echo htmlspecialchars($pr['total_membros'] ?? '—'); ?> membros
                                </div>
                            </div>
                            <div class="dash-proxima-dia">
                                <?php echo htmlspecialchars($pr['dia_semana']); ?><br>
                                <span><?php echo htmlspecialchars($pr['horario']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-admin-main">
        <div class="presencas-card">
            <h2>Meus Grupos Familiares</h2>
            <?php if (count($gruposDoLider) === 0): ?>
                <p style="color: var(--color-text-muted); font-size: 13px;">Você ainda não está vinculado como líder de nenhum Grupo Familiar.</p>
            <?php else: ?>
                <?php renderDashboardGruposFamiliaresV2($gruposDoLider); ?>
                <div class="acoes" style="margin-top: 8px;">
                    <a class="botao-link" href="/presencas.php">Ir para Reuniões e Presenças</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="presencas-card quadro-ultimas-reunioes">
            <h2>Últimas reuniões</h2>
            <?php if (count($ultimasReunioes) === 0): ?>
                <p style="color: var(--color-text-muted); font-size: 13px;">Nenhuma reunião registrada ainda.</p>
            <?php else: ?>
                <div class="tabela-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>GF</th>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Pres.</th>
                                <th>Aus.</th>
                                <th class="tabela-acoes">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasReunioes as $reuniao): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reuniao['grupo_nome']); ?></td>
                                    <td><?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?></td>
                                    <td><?php echo htmlspecialchars($reuniao['horario']); ?></td>
                                    <td><?php echo htmlspecialchars($reuniao['total_presentes']); ?></td>
                                    <td><?php echo htmlspecialchars($reuniao['total_ausentes']); ?></td>
                                    <td class="tabela-acoes">
                                        <div class="acoes">
                                            <a class="botao-link" href="/reuniao_visualizar.php?id=<?php echo (int) $reuniao['id']; ?>">Visualizar</a>
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
<?php else: ?>
    <div class="presencas-layout">
        <div class="presencas-coluna">
            <div class="presencas-card">
                <h2>Meus Grupos Familiares</h2>
                <?php if (count($gruposDoLider) === 0): ?>
                    <p style="color: var(--color-text-muted); font-size: 13px;">Você ainda não está vinculado como líder de nenhum Grupo Familiar.</p>
                <?php else: ?>
                    <?php renderDashboardGruposFamiliaresV2($gruposDoLider); ?>
                    <div class="acoes" style="margin-top: 8px;">
                        <a class="botao-link" href="/presencas.php">Ir para Reuniões e Presenças</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="presencas-coluna">
            <div class="presencas-card quadro-ultimas-reunioes quadro-ultimas-reunioes-lider">
                <h2>Últimas reuniões dos meus GFs</h2>
                <?php if (count($ultimasReunioes) === 0): ?>
                    <p style="color: var(--color-text-muted); font-size: 13px;">Nenhuma reunião registrada ainda.</p>
                <?php else: ?>
                    <div class="tabela-wrapper tabela-wrapper-lider">
                        <table class="tabela-reunioes-lider">
                            <thead>
                                <tr>
                                    <th>GF</th>
                                    <th>Data</th>
                                    <th>Horário</th>
                                    <th class="tabela-acoes">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimasReunioes as $reuniao): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reuniao['grupo_nome']); ?></td>
                                        <td><?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?></td>
                                        <td><?php echo htmlspecialchars($reuniao['horario']); ?></td>
                                        <td class="tabela-acoes">
                                            <div class="acoes-reuniao-lider">
                                                <a class="botao-link" href="/reuniao_visualizar.php?id=<?php echo (int) $reuniao['id']; ?>">Visualizar</a>
                                                <a class="botao-link btn-presenca-oracao" href="/pedidos_oracao.php?reuniao_id=<?php echo (int) $reuniao['id']; ?>">Oração</a>
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
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
