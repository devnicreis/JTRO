<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <h1>Visualizar Reunião</h1>
</div>

<div class="presencas-layout">

    <!-- Coluna esquerda: dados da reunião -->
    <div class="presencas-coluna">
        <div class="presencas-card">
            <h2 style="font-size:16px; margin-bottom:16px;">
                <?php echo htmlspecialchars($reuniao['grupo_nome']); ?>
                <span style="color:var(--color-text-muted); font-weight:400;"> — </span>
                <?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?>
            </h2>

            <div class="acoes" style="margin-bottom: 20px;">
                <a class="btn-presenca-oracao" href="/pedidos_oracao.php?reuniao_id=<?php echo (int) $reuniao['id']; ?>">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M8 2v12M2 8h12"/></svg>
                    Pedidos de Oração
                </a>
            </div>

            <p><strong>Líder(es):</strong> <?php echo htmlspecialchars($lideres); ?></p>
            <p><strong>Horário:</strong> <?php echo htmlspecialchars($reuniao['horario']); ?></p>
            <p><strong>Local:</strong> <?php echo htmlspecialchars($reuniao['local'] ?? '—'); ?></p>
            <?php if (!empty($reuniao['observacoes'])): ?>
                <p><strong>Observações:</strong> <?php echo htmlspecialchars($reuniao['observacoes']); ?></p>
            <?php endif; ?>
            <?php if (!empty($reuniao['motivo_alteracao'])): ?>
                <p><strong>Motivo de alteração:</strong> <?php echo htmlspecialchars($reuniao['motivo_alteracao']); ?></p>
            <?php endif; ?>

            <hr>

            <p>
                <strong>Presentes:</strong> <?php echo htmlspecialchars($resumoReuniao['total_presentes']); ?>
                &nbsp;·&nbsp;
                <strong>Ausentes:</strong> <?php echo htmlspecialchars($resumoReuniao['total_ausentes']); ?>
            </p>

            <div class="barra-percentual" style="height:10px; margin:10px 0 8px;">
                <div class="barra-presenca" style="width:<?php echo (float)$resumoReuniao['percentual_presencas']; ?>%;"></div>
                <div class="barra-ausencia" style="width:<?php echo (float)$resumoReuniao['percentual_ausencias']; ?>%;"></div>
            </div>
            <div class="resumo-percentual">
                <span class="legenda-presenca">Presença: <?php echo $resumoReuniao['percentual_presencas']; ?>%</span>
                <span class="legenda-ausencia">Ausência: <?php echo $resumoReuniao['percentual_ausencias']; ?>%</span>
            </div>

            <!-- Tabela sem CPF e sem Perfil do Sistema -->
            <div class="tabela-wrapper" style="margin-top:16px;">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaPresencas as $presenca): ?>
                            <?php
                            $status = strtolower($presenca['status']);
                            $corStatus = $status === 'presente'
                                ? 'var(--color-green)'
                                : ($status === 'ausente' ? 'var(--color-red)' : 'var(--color-text-muted)');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($presenca['nome']); ?></td>
                                <td style="font-weight:500; color:<?php echo $corStatus; ?>;">
                                    <?php echo htmlspecialchars(ucfirst($presenca['status'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Coluna direita: resumo do GF + últimas reuniões -->
    <div class="presencas-coluna">

        <div class="presencas-card" style="margin-bottom:20px;">
            <h2>Resumo do Grupo Familiar</h2>

            <div class="cards-resumo" style="grid-template-columns: repeat(2, 1fr); margin-bottom:0;">
                <div class="card-resumo card-resumo-azul">
                    <div class="card-resumo-label">Membros ativos</div>
                    <div class="numero"><?php echo htmlspecialchars($resumoGrupo['total_membros_ativos'] ?? 0); ?></div>
                </div>
                <div class="card-resumo card-resumo-verde">
                    <div class="card-resumo-label">Total de reuniões</div>
                    <div class="numero"><?php echo htmlspecialchars($resumoGrupo['total_reunioes'] ?? 0); ?></div>
                </div>
                <div class="card-resumo card-resumo-roxo" style="margin-top:10px;">
                    <div class="card-resumo-label">Última reunião</div>
                    <div class="numero" style="font-size:16px; padding-top:4px;">
                        <?php echo htmlspecialchars(formatarDataBr($resumoGrupo['ultima_data_reuniao'] ?? null)); ?>
                    </div>
                </div>
                <div class="card-resumo card-resumo-amber" style="margin-top:10px;">
                    <div class="card-resumo-label">Local padrão</div>
                    <div class="numero" style="font-size:15px; padding-top:4px;">
                        <?php echo htmlspecialchars($resumoGrupo['local_padrao'] ?? '—'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="presencas-card">
            <h2>Últimas reuniões</h2>

            <?php if (count($ultimasReunioes) === 0): ?>
                <p style="color:var(--color-text-muted); font-size:13px;">Ainda não há reuniões registradas.</p>
            <?php else: ?>
                <div class="tabela-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Local</th>
                                <th>Pres.</th>
                                <th>Aus.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasReunioes as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(formatarDataBr($item['data'])); ?></td>
                                    <td><?php echo htmlspecialchars($item['horario']); ?></td>
                                    <td><?php echo htmlspecialchars($item['local'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($item['total_presentes']); ?></td>
                                    <td><?php echo htmlspecialchars($item['total_ausentes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>