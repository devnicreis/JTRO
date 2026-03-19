<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="topo-dashboard">
    <div class="menu-esquerda">
        <a href="/meu_perfil.php">Meu Perfil</a> |
        <a href="/logout.php">Sair</a>
    </div>

    <div class="menu-direita">
        <a href="/avisos.php" class="atalho-avisos">
            Avisos
            <?php if (($totalAvisos ?? 0) > 0): ?>
                <span class="badge-aviso"><?php echo $totalAvisos; ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<h1>COMUNHÃO CRISTÃ ABBA FAZENDA RIO GRANDE</h1>
<h3>JTRO: seu organizador relacional</h3>

<p>Olá, <?php echo htmlspecialchars($usuario['nome']); ?>.</p>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($dashboardTipo === 'admin'): ?>
    <div class="cards-resumo">
        <div class="card-resumo">
            <h3>Pessoas ativas</h3>
            <div class="numero"><?php echo $totalPessoasAtivas; ?></div>
        </div>

        <div class="card-resumo">
            <h3>Líderes ativos</h3>
            <div class="numero"><?php echo $totalLideresAtivos; ?></div>
        </div>

        <div class="card-resumo">
            <h3>GFs ativos</h3>
            <div class="numero"><?php echo $totalGruposAtivos; ?></div>
        </div>

        <div class="card-resumo">
            <h3>Reuniões registradas</h3>
            <div class="numero"><?php echo $totalReunioes; ?></div>
        </div>

        <div class="card-resumo">
            <h3>Presenças atualizadas</h3>
            <div class="numero"><?php echo $totalPresencasAtualizadas; ?></div>
        </div>
    </div>

    <div class="presencas-layout">
        <div class="presencas-coluna">
            <div class="presencas-card">
                <h2>Acessos rápidos</h2>
                <div class="acoes">
                    <a class="botao-link" href="/pessoas.php">Pessoas</a>
                    <a class="botao-link" href="/grupos_familiares.php">Grupos Familiares</a>
                    <a class="botao-link" href="/presencas.php">Reuniões e Presenças</a>
                    <a class="botao-link" href="/auditoria.php">Auditoria</a>
                </div>
            </div>

            <div class="presencas-card" style="margin-top: 24px;">
                <h2>Diagnóstico de GFs</h2>

                <form method="GET" action="/index.php" style="margin-bottom: 20px;">
                    <div class="campo">
                        <label for="grupo_id">Escolha um Grupo Familiar</label>
                        <select id="grupo_id" name="grupo_id">
                            <option value="">Selecione um GF</option>
                            <?php foreach ($gruposAtivosLista as $grupoFiltro): ?>
                                <?php if ((int) ($grupoFiltro['ativo'] ?? 1) !== 1) continue; ?>
                                <option value="<?php echo (int) $grupoFiltro['id']; ?>" <?php echo isset($grupoSelecionadoId) && $grupoSelecionadoId === (int) $grupoFiltro['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($grupoFiltro['nome']); ?>
                                    <?php if (!empty($grupoFiltro['lideres'])): ?>
                                        — <?php echo htmlspecialchars($grupoFiltro['lideres']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="acoes">
                        <button type="submit">Ver diagnóstico</button>
                        <a class="botao-link botao-secundario" href="/index.php">Limpar seleção</a>
                    </div>
                </form>

                <?php if (empty($gruposResumoAdmin)): ?>
                    <p>Selecione um Grupo Familiar para visualizar o diagnóstico.</p>
                <?php else: ?>
                    <?php foreach ($gruposResumoAdmin as $item): ?>
                        <div class="card-perfil" style="margin-bottom: 20px;">
                            <div class="diagnostico-faixa <?php echo htmlspecialchars($item['diagnostico_classe']); ?>">
                                Diagnóstico: <?php echo htmlspecialchars($item['diagnostico']); ?>
                            </div>

                            <h3><?php echo htmlspecialchars($item['grupo']['nome']); ?></h3>

                            <p><strong>Líder(es):</strong> <?php echo htmlspecialchars($item['grupo']['lideres'] ?? '—'); ?></p>
                            <p><strong>Dia:</strong> <?php echo htmlspecialchars($item['grupo']['dia_semana']); ?></p>
                            <p><strong>Horário:</strong> <?php echo htmlspecialchars($item['grupo']['horario']); ?></p>
                            <p><strong>Membros ativos:</strong> <?php echo htmlspecialchars($item['grupo']['total_membros'] ?? '—'); ?></p>
                            <p><strong>Total de reuniões:</strong> <?php echo htmlspecialchars($item['grupo']['total_reunioes'] ?? '—'); ?></p>
                            <p><strong>Última reunião:</strong> <?php echo !empty($item['grupo']['ultima_reuniao']) ? htmlspecialchars(formatarDataBr($item['grupo']['ultima_reuniao'])) : '—'; ?></p>

                            <hr style="margin: 16px 0;">

                            <?php
                            $presencas = (int) $item['resumo_presenca']['total_presencas'];
                            $ausencias = (int) $item['resumo_presenca']['total_ausencias'];
                            $percentualPresenca = (float) $item['resumo_presenca']['percentual_presencas'];
                            $percentualAusencia = (float) $item['resumo_presenca']['percentual_ausencias'];
                            ?>

                            <p>
                                <strong>Presenças:</strong> <?php echo $presencas; ?> |
                                <strong>Ausências:</strong> <?php echo $ausencias; ?>
                            </p>

                            <div class="barra-percentual">
                                <div class="barra-presenca" style="width: <?php echo $percentualPresenca; ?>%;"></div>
                                <div class="barra-ausencia" style="width: <?php echo $percentualAusencia; ?>%;"></div>
                            </div>

                            <div class="resumo-percentual">
                                <span class="legenda-presenca">Presença: <?php echo $percentualPresenca; ?>%</span>
                                <span class="legenda-ausencia">Ausência: <?php echo $percentualAusencia; ?>%</span>
                            </div>

                            <?php if (!empty($item['faltosos'])): ?>
                                <div class="erro" style="margin-top: 12px;">
                                    <strong>Avisos:</strong><br>
                                    <?php foreach ($item['faltosos'] as $faltoso): ?>
                                        <?php echo htmlspecialchars($faltoso['nome']); ?> — <?php echo htmlspecialchars($faltoso['faltas_consecutivas']); ?> faltas consecutivas<br>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($item['resumo_membros'])): ?>
                                <table style="margin-top: 16px;">
                                    <thead>
                                        <tr>
                                            <th>Membro</th>
                                            <th>Última presença</th>
                                            <th>Presenças</th>
                                            <th>Ausências</th>
                                            <th>% Presença</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($item['resumo_membros'] as $membro): ?>
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
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="presencas-coluna">
            <div class="presencas-card quadro-ultimas-reunioes">
                <h2>Últimas reuniões</h2>


                <?php if (count($ultimasReunioes) === 0): ?>
                    <p>Nenhuma reunião registrada ainda.</p>
                <?php else: ?>
                    <div class="tabela-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>GF</th>
                                    <th>Data</th>
                                    <th>Horário</th>
                                    <th>Presentes</th>
                                    <th>Ausentes</th>
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
    </div>

<?php else: ?>
    <div class="presencas-layout">
        <div class="presencas-coluna">
            <div class="presencas-card">
                <h2>Meus Grupos Familiares</h2>

                <?php if (count($gruposDoLider) === 0): ?>
                    <p>Você ainda não está vinculado como líder de nenhum Grupo Familiar.</p>
                <?php else: ?>
                    <?php foreach ($gruposDoLider as $grupo): ?>
                        <div class="card-perfil" style="margin-bottom: 20px;">
                            <h3><?php echo htmlspecialchars($grupo['nome']); ?></h3>
                            <p><strong>Dia:</strong> <?php echo htmlspecialchars($grupo['dia_semana']); ?></p>
                            <p><strong>Horário:</strong> <?php echo htmlspecialchars($grupo['horario']); ?></p>
                            <p><strong>Membros ativos:</strong> <?php echo htmlspecialchars($grupo['total_membros_ativos']); ?></p>
                            <p><strong>Total de reuniões:</strong> <?php echo htmlspecialchars($grupo['total_reunioes']); ?></p>
                            <p><strong>Última reunião:</strong> <?php echo !empty($grupo['ultima_reuniao']) ? htmlspecialchars(formatarDataBr($grupo['ultima_reuniao'])) : '—'; ?></p>

                            <hr style="margin: 16px 0;">

                            <?php
                            $presencas = (int) $grupo['resumo_presenca']['total_presencas'];
                            $ausencias = (int) $grupo['resumo_presenca']['total_ausencias'];
                            $percentualPresenca = (float) $grupo['resumo_presenca']['percentual_presencas'];
                            $percentualAusencia = (float) $grupo['resumo_presenca']['percentual_ausencias'];
                            ?>

                            <p>
                                <strong>Presenças:</strong> <?php echo $presencas; ?> |
                                <strong>Ausências:</strong> <?php echo $ausencias; ?>
                            </p>

                            <div class="barra-percentual">
                                <div class="barra-presenca" style="width: <?php echo $percentualPresenca; ?>%;"></div>
                                <div class="barra-ausencia" style="width: <?php echo $percentualAusencia; ?>%;"></div>
                            </div>

                            <div class="resumo-percentual">
                                <span class="legenda-presenca">Presença: <?php echo $percentualPresenca; ?>%</span>
                                <span class="legenda-ausencia">Ausência: <?php echo $percentualAusencia; ?>%</span>
                            </div>

                            <?php if (!empty($grupo['faltosos'])): ?>
                                <div class="erro" style="margin-top: 12px;">
                                    <strong>Avisos:</strong><br>
                                    <?php foreach ($grupo['faltosos'] as $faltoso): ?>
                                        <?php echo htmlspecialchars($faltoso['nome']); ?> — <?php echo htmlspecialchars($faltoso['faltas_consecutivas']); ?> faltas consecutivas<br>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($grupo['resumo_membros'])): ?>
                                <table style="margin-top: 16px;">
                                    <thead>
                                        <tr>
                                            <th>Membro</th>
                                            <th>Última presença</th>
                                            <th>Presenças</th>
                                            <th>Ausências</th>
                                            <th>% Presença</th>
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
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="acoes" style="margin-top: 16px;">
                    <a class="botao-link" href="/presencas.php">Ir para Reuniões e Presenças</a>
                </div>
            </div>
        </div>

        <div class="presencas-coluna">
            <div class="presencas-card quadro-ultimas-reunioes">
                <h2>Últimas reuniões dos meus GFs</h2>

                <?php if (count($ultimasReunioes) === 0): ?>
                    <p>Nenhuma reunião registrada ainda.</p>
                <?php else: ?>
                    <div class="tabela-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>GF</th>
                                    <th>Data</th>
                                    <th>Horário</th>
                                    <th>Presentes</th>
                                    <th>Ausentes</th>
                                    <th class="tabela-acoes">Ações</th>
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
                                                <a class="botao-link" href="/pedidos_oracao.php?reuniao_id=<?php echo (int) $reuniao['id']; ?>">Pedidos de Oração</a>
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