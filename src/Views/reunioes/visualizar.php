<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/index.php">← Voltar para início</a>
</div>

<h1>Visualizar Reunião</h1>

<div class="presencas-layout">
    <div class="presencas-coluna">
        <div class="presencas-card">
            <h2><?php echo htmlspecialchars($reuniao['grupo_nome']); ?> — <?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?></h2>

            <div class="acoes" style="margin-bottom: 16px;">
                <a class="botao-link" href="/pedidos_oracao.php?reuniao_id=<?php echo (int) $reuniao['id']; ?>">
                    Pedidos de Oração
                </a>
            </div>


            <p><strong>Líder(es):</strong> <?php echo htmlspecialchars($lideres); ?></p>
            <p><strong>Horário:</strong> <?php echo htmlspecialchars($reuniao['horario']); ?></p>
            <p><strong>Local:</strong> <?php echo htmlspecialchars($reuniao['local'] ?? '—'); ?></p>
            <p><strong>Observações:</strong> <?php echo htmlspecialchars($reuniao['observacoes'] ?? '—'); ?></p>
            <p><strong>Motivo de alteração:</strong> <?php echo htmlspecialchars($reuniao['motivo_alteracao'] ?? '—'); ?></p>

            <hr style="margin: 16px 0;">

            <p>
                <strong>Presentes:</strong> <?php echo htmlspecialchars($resumoReuniao['total_presentes']); ?> |
                <strong>Ausentes:</strong> <?php echo htmlspecialchars($resumoReuniao['total_ausentes']); ?>
            </p>

            <div class="barra-percentual">
                <div class="barra-presenca" style="width: <?php echo (float) $resumoReuniao['percentual_presencas']; ?>%;"></div>
                <div class="barra-ausencia" style="width: <?php echo (float) $resumoReuniao['percentual_ausencias']; ?>%;"></div>
            </div>

            <div class="resumo-percentual">
                <span class="legenda-presenca">Presença: <?php echo htmlspecialchars($resumoReuniao['percentual_presencas']); ?>%</span>
                <span class="legenda-ausencia">Ausência: <?php echo htmlspecialchars($resumoReuniao['percentual_ausencias']); ?>%</span>
            </div>

            <table style="margin-top: 16px;">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Perfil do sistema</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listaPresencas as $presenca): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($presenca['nome']); ?></td>
                            <td><?php echo htmlspecialchars($presenca['cpf']); ?></td>
                            <td><?php echo htmlspecialchars($presenca['cargo']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($presenca['status'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="presencas-coluna">
        <div class="quadro-reunioes">
            <h2>Resumo do Grupo Familiar</h2>

            <div class="cards-resumo">
                <div class="card-resumo">
                    <h3>Membros ativos</h3>
                    <div class="numero"><?php echo htmlspecialchars($resumoGrupo['total_membros_ativos'] ?? 0); ?></div>
                </div>

                <div class="card-resumo">
                    <h3>Total de reuniões</h3>
                    <div class="numero"><?php echo htmlspecialchars($resumoGrupo['total_reunioes'] ?? 0); ?></div>
                </div>

                <div class="card-resumo">
                    <h3>Última reunião</h3>
                    <div class="numero" style="font-size: 16px;">
                        <?php echo htmlspecialchars(formatarDataBr($resumoGrupo['ultima_data_reuniao'] ?? null)); ?>
                    </div>
                </div>

                <div class="card-resumo">
                    <h3>Local padrão</h3>
                    <div class="numero" style="font-size: 16px;">
                        <?php echo htmlspecialchars($resumoGrupo['local_padrao'] ?? '—'); ?>
                    </div>
                </div>
            </div>

            <h2>Últimas reuniões</h2>

            <?php if (count($ultimasReunioes) === 0): ?>
                <p>Ainda não há reuniões registradas para este GF.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Horário</th>
                            <th>Local</th>
                            <th>Presentes</th>
                            <th>Ausentes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimasReunioes as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(formatarDataBr($item['data'])); ?></td>
                                <td><?php echo htmlspecialchars($item['horario']); ?></td>
                                <td><?php echo htmlspecialchars($item['local'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($item['total_presentes']); ?></td>
                                <td><?php echo htmlspecialchars($item['total_ausentes']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>