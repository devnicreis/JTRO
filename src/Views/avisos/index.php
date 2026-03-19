<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/index.php">← Voltar para dashboard</a>
</div>

<h1>Avisos</h1>

<div class="presencas-layout">
    <div class="presencas-coluna">
        <div class="presencas-card">
            <h2>Não lidos</h2>

            <?php if (empty($avisosNaoLidos)): ?>
                <p>Nenhum aviso não lido.</p>
            <?php else: ?>
                <?php foreach ($avisosNaoLidos as $aviso): ?>
                    <div class="card-perfil" style="margin-bottom: 16px;">
                        <?php if ($aviso['tipo'] === 'grupo_alarmante'): ?>
                            <?php $grupo = $aviso['grupo']; ?>
                            <div class="diagnostico-faixa diagnostico-alarmante">Diagnóstico: ALARMANTE</div>
                            <h3><?php echo htmlspecialchars($grupo['nome']); ?></h3>
                            <p><strong>Líder(es):</strong> <?php echo htmlspecialchars($grupo['lideres'] ?? '—'); ?></p>
                            <p><strong>Dia:</strong> <?php echo htmlspecialchars($grupo['dia_semana']); ?></p>
                            <p><strong>Horário:</strong> <?php echo htmlspecialchars($grupo['horario']); ?></p>
                            <p><strong>Presenças:</strong> <?php echo htmlspecialchars($grupo['resumo_presenca']['total_presencas']); ?></p>
                            <p><strong>Ausências:</strong> <?php echo htmlspecialchars($grupo['resumo_presenca']['total_ausencias']); ?></p>
                            <p><strong>% Presença:</strong> <?php echo htmlspecialchars($grupo['resumo_presenca']['percentual_presencas']); ?>%</p>

                        <?php elseif ($aviso['tipo'] === 'faltas_consecutivas'): ?>
                            <?php $membro = $aviso['membro']; ?>
                            <h3><?php echo htmlspecialchars($membro['nome']); ?></h3>
                            <p><strong>Grupo Familiar:</strong> <?php echo htmlspecialchars($membro['grupo_nome']); ?></p>
                            <p><strong>Líder(es):</strong> <?php echo htmlspecialchars($membro['lideres'] ?? '—'); ?></p>
                            <p><strong>Faltas consecutivas:</strong> <?php echo htmlspecialchars($membro['faltas_consecutivas']); ?></p>

                        <?php elseif ($aviso['tipo'] === 'reuniao_fora_padrao'): ?>
                            <?php $reuniao = $aviso['reuniao']; ?>
                            <div class="diagnostico-faixa diagnostico-atencao">Reunião fora do padrão</div>
                            <h3><?php echo htmlspecialchars($reuniao['grupo_nome']); ?></h3>
                            <p><strong>Líder(es):</strong> <?php echo htmlspecialchars($reuniao['lideres'] ?? '—'); ?></p>
                            <p><strong>Data:</strong> <?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?></p>
                            <p><strong>Horário:</strong> <?php echo htmlspecialchars($reuniao['horario']); ?></p>
                            <p><strong>Motivo:</strong> <?php echo htmlspecialchars($reuniao['motivo_alteracao']); ?></p>
                        <?php endif; ?>

                        <form method="POST" style="margin-top: 12px;">
                            <input type="hidden" name="acao" value="marcar_lido">
                            <input type="hidden" name="chave_aviso" value="<?php echo htmlspecialchars($aviso['chave']); ?>">
                            <button type="submit">Marcar como lido</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="presencas-coluna">
        <div class="presencas-card">
            <h2>Lidos</h2>

            <?php if (empty($avisosLidos)): ?>
                <p>Nenhum aviso lido.</p>
            <?php else: ?>
                <div class="bloco-avisos-rolagem">
                    <?php foreach ($avisosLidos as $aviso): ?>
                        <div class="card-perfil" style="margin-bottom: 16px;">
                            <?php if ($aviso['tipo'] === 'grupo_alarmante'): ?>
                                <?php $grupo = $aviso['grupo']; ?>
                                <div class="diagnostico-faixa diagnostico-alarmante">Diagnóstico: ALARMANTE</div>
                                <h3><?php echo htmlspecialchars($grupo['nome']); ?></h3>
                                <p><strong>Líder(es):</strong> <?php echo htmlspecialchars($grupo['lideres'] ?? '—'); ?></p>
                                <p><strong>Dia:</strong> <?php echo htmlspecialchars($grupo['dia_semana']); ?></p>
                                <p><strong>Horário:</strong> <?php echo htmlspecialchars($grupo['horario']); ?></p>
                                <p><strong>Presenças:</strong> <?php echo htmlspecialchars($grupo['resumo_presenca']['total_presencas']); ?></p>
                                <p><strong>Ausências:</strong> <?php echo htmlspecialchars($grupo['resumo_presenca']['total_ausencias']); ?></p>
                                <p><strong>% Presença:</strong> <?php echo htmlspecialchars($grupo['resumo_presenca']['percentual_presencas']); ?>%</p>

                            <?php elseif ($aviso['tipo'] === 'faltas_consecutivas'): ?>
                                <?php $membro = $aviso['membro']; ?>
                                <h3><?php echo htmlspecialchars($membro['nome']); ?></h3>
                                <p><strong>Grupo Familiar:</strong> <?php echo htmlspecialchars($membro['grupo_nome']); ?></p>
                                <p><strong>Líder(es):</strong> <?php echo htmlspecialchars($membro['lideres'] ?? '—'); ?></p>
                                <p><strong>Faltas consecutivas:</strong> <?php echo htmlspecialchars($membro['faltas_consecutivas']); ?></p>

                            <?php elseif ($aviso['tipo'] === 'reuniao_fora_padrao'): ?>
                                <?php $reuniao = $aviso['reuniao']; ?>
                                <div class="diagnostico-faixa diagnostico-atencao">Reunião fora do padrão</div>
                                <h3><?php echo htmlspecialchars($reuniao['grupo_nome']); ?></h3>
                                <p><strong>Líder(es):</strong> <?php echo htmlspecialchars($reuniao['lideres'] ?? '—'); ?></p>
                                <p><strong>Data:</strong> <?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?></p>
                                <p><strong>Horário:</strong> <?php echo htmlspecialchars($reuniao['horario']); ?></p>
                                <p><strong>Motivo:</strong> <?php echo htmlspecialchars($reuniao['motivo_alteracao']); ?></p>
                            <?php endif; ?>

                            <form method="POST" style="margin-top: 12px;">
                                <input type="hidden" name="acao" value="marcar_nao_lido">
                                <input type="hidden" name="chave_aviso" value="<?php echo htmlspecialchars($aviso['chave']); ?>">
                                <button type="submit">Marcar como não lido</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require __DIR__ . '/../layouts/footer.php'; ?>