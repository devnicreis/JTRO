<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <h1>Notificações</h1>
</div>

<div class="presencas-layout">

    <!-- Coluna esquerda: não lidos -->
    <div class="presencas-coluna">
        <div class="presencas-card">
            <h2>
                Não lidas
                <?php if (!empty($avisosNaoLidos)): ?>
                    <span class="aviso-count-badge"><?php echo count($avisosNaoLidos); ?></span>
                <?php endif; ?>
            </h2>

            <?php if (empty($avisosNaoLidos)): ?>
                <p style="color: var(--color-text-muted); font-size: 13px;">Nenhuma notificação não lida.</p>
            <?php else: ?>
                <div id="lista-nao-lidos">
                    <?php foreach ($avisosNaoLidos as $aviso): ?>
                        <div class="aviso-item aviso-nao-lido" data-chave="<?php echo htmlspecialchars($aviso['chave']); ?>">

                            <?php if ($aviso['tipo'] === 'carta_nova'): ?>
                                <?php $carta = $aviso['carta']; ?>
                                <div class="aviso-item-topo">
                                    <span class="diagnostico-faixa diagnostico-bom">Nova Carta Semanal</span>
                                </div>
                                <div class="aviso-item-titulo"><?php echo htmlspecialchars($carta['pregacao_titulo'] ?? 'Carta do Pastor'); ?></div>
                                <div class="aviso-item-detalhe">
                                    <span><strong>Data:</strong> <?php echo htmlspecialchars(formatarDataBr($carta['data_carta'])); ?></span>
                                    <span><a href="/carta_visualizar.php?id=<?php echo (int)$carta['id']; ?>" class="aviso-link-acao">ACESSAR CARTA COMPLETA</a></span>
                                </div>

                            <?php elseif ($aviso['tipo'] === 'grupo_alarmante'): ?>
                                <?php $grupo = $aviso['grupo']; ?>
                                <div class="aviso-item-topo">
                                    <span class="diagnostico-faixa diagnostico-alarmante">Diagnóstico alarmante</span>
                                </div>
                                <div class="aviso-item-titulo"><?php echo htmlspecialchars($grupo['nome']); ?></div>
                                <div class="aviso-item-detalhe">
                                    <span><strong>Líder(es):</strong> <?php echo htmlspecialchars($grupo['lideres'] ?? '—'); ?></span>
                                    <span><strong>Presença:</strong> <?php echo htmlspecialchars($grupo['resumo_presenca']['percentual_presencas']); ?>%</span>
                                    <span><strong>Dia:</strong> <?php echo htmlspecialchars($grupo['dia_semana']); ?> às <?php echo htmlspecialchars($grupo['horario']); ?></span>
                                    <span><a href="/diagnostico_gf.php?grupo_id=<?php echo (int) $grupo['id']; ?>" class="aviso-link-acao">Abrir detalhes</a></span>
                                </div>

                            <?php elseif ($aviso['tipo'] === 'faltas_consecutivas'): ?>
                                <?php $membro = $aviso['membro']; ?>
                                <div class="aviso-item-topo">
                                    <span class="diagnostico-faixa diagnostico-atencao">Faltas consecutivas</span>
                                </div>
                                <div class="aviso-item-titulo"><?php echo htmlspecialchars($membro['nome']); ?></div>
                                <div class="aviso-item-detalhe">
                                    <span><strong>GF:</strong> <?php echo htmlspecialchars($membro['grupo_nome']); ?></span>
                                    <span><strong>Líder(es):</strong> <?php echo htmlspecialchars($membro['lideres'] ?? '—'); ?></span>
                                    <span><strong>Faltas:</strong> <?php echo htmlspecialchars($membro['faltas_consecutivas']); ?> consecutivas</span>
                                    <span><a href="/diagnostico_gf.php?grupo_id=<?php echo (int) $membro['grupo_id']; ?>" class="aviso-link-acao">Abrir detalhes</a></span>
                                </div>

                            <?php elseif ($aviso['tipo'] === 'reuniao_fora_padrao'): ?>
                                <?php $reuniao = $aviso['reuniao']; ?>
                                <div class="aviso-item-topo">
                                    <span class="diagnostico-faixa diagnostico-atencao">Reunião fora do padrão</span>
                                </div>
                                <div class="aviso-item-titulo"><?php echo htmlspecialchars($reuniao['grupo_nome']); ?></div>
                                <div class="aviso-item-detalhe">
                                    <span><strong>Líder(es):</strong> <?php echo htmlspecialchars($reuniao['lideres'] ?? '—'); ?></span>
                                    <span><strong>Data:</strong> <?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?> às <?php echo htmlspecialchars($reuniao['horario']); ?></span>
                                    <span><strong>Motivo:</strong> <?php echo htmlspecialchars($reuniao['motivo_alteracao']); ?></span>
                                </div>
                            <?php elseif ($aviso['tipo'] === 'aviso_sistema'): ?>
                                <div class="aviso-item-topo">
                                    <span class="diagnostico-faixa diagnostico-bom">
                                        <?php echo htmlspecialchars(($aviso['subtipo'] ?? '') === 'integracao_concluida' ? 'Integração concluída' : 'Aviso do sistema'); ?>
                                    </span>
                                </div>
                                <div class="aviso-item-titulo"><?php echo htmlspecialchars($aviso['titulo'] ?? 'Aviso'); ?></div>
                                <div class="aviso-item-detalhe">
                                    <span><?php echo htmlspecialchars($aviso['mensagem'] ?? ''); ?></span>
                                    <?php if (!empty($aviso['created_at'])): ?>
                                        <span><strong>Data:</strong> <?php echo htmlspecialchars(formatarDataHoraBr($aviso['created_at'])); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($aviso['link']) && ($aviso['subtipo'] ?? '') !== 'aniversario'): ?>
                                        <span><a href="<?php echo htmlspecialchars($aviso['link']); ?>" style="color:var(--color-blue); font-size:12px;">Abrir detalhes</a></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <button
                                class="aviso-btn-acao"
                                data-chave="<?php echo htmlspecialchars($aviso['chave']); ?>"
                                data-acao="marcar_lido"
                                type="button">
                                Marcar como lida
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Coluna direita: lidos com scroll -->
    <div class="presencas-coluna" id="col-lidos">
        <div class="presencas-card">
            <h2>Lidas</h2>

            <?php if (empty($avisosLidos)): ?>
                <p style="color: var(--color-text-muted); font-size: 13px;">Nenhuma notificação lida.</p>
            <?php else: ?>
                <div class="avisos-lidos-scroll" id="lista-lidos">
                    <?php foreach ($avisosLidos as $aviso): ?>
                        <div class="aviso-item aviso-lido" data-chave="<?php echo htmlspecialchars($aviso['chave']); ?>">

                            <?php if ($aviso['tipo'] === 'carta_nova'): ?>
                                <?php $carta = $aviso['carta']; ?>
                                <div class="aviso-item-titulo aviso-titulo-lido"><?php echo htmlspecialchars($carta['pregacao_titulo'] ?? 'Carta do Pastor'); ?></div>
                                <div class="aviso-item-detalhe">
                                    <span>Carta Semanal · <?php echo htmlspecialchars(formatarDataBr($carta['data_carta'])); ?></span>
                                    <span><a href="/carta_visualizar.php?id=<?php echo (int)$carta['id']; ?>" class="aviso-link-acao">ACESSAR CARTA COMPLETA</a></span>
                                </div>

                            <?php elseif ($aviso['tipo'] === 'grupo_alarmante'): ?>
                                <?php $grupo = $aviso['grupo']; ?>
                                <div class="aviso-item-titulo aviso-titulo-lido"><?php echo htmlspecialchars($grupo['nome']); ?></div>
                                <div class="aviso-item-detalhe">
                                    <span>Diagnóstico alarmante · <?php echo htmlspecialchars($grupo['resumo_presenca']['percentual_presencas']); ?>% presença</span>
                                    <span><a href="/diagnostico_gf.php?grupo_id=<?php echo (int) $grupo['id']; ?>" class="aviso-link-acao">Abrir detalhes</a></span>
                                </div>

                            <?php elseif ($aviso['tipo'] === 'faltas_consecutivas'): ?>
                                <?php $membro = $aviso['membro']; ?>
                                <div class="aviso-item-titulo aviso-titulo-lido"><?php echo htmlspecialchars($membro['nome']); ?></div>
                                <div class="aviso-item-detalhe">
                                    <span><?php echo htmlspecialchars($membro['faltas_consecutivas']); ?> faltas · GF <?php echo htmlspecialchars($membro['grupo_nome']); ?></span>
                                    <span><a href="/diagnostico_gf.php?grupo_id=<?php echo (int) $membro['grupo_id']; ?>" class="aviso-link-acao">Abrir detalhes</a></span>
                                </div>

                            <?php elseif ($aviso['tipo'] === 'reuniao_fora_padrao'): ?>
                                <?php $reuniao = $aviso['reuniao']; ?>
                                <div class="aviso-item-titulo aviso-titulo-lido"><?php echo htmlspecialchars($reuniao['grupo_nome']); ?></div>
                                <div class="aviso-item-detalhe">
                                    <span>Reunião fora do padrão · <?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?></span>
                                </div>
                            <?php elseif ($aviso['tipo'] === 'aviso_sistema'): ?>
                                <div class="aviso-item-titulo aviso-titulo-lido"><?php echo htmlspecialchars($aviso['titulo'] ?? 'Aviso'); ?></div>
                                <div class="aviso-item-detalhe">
                                    <span><?php echo htmlspecialchars($aviso['mensagem'] ?? ''); ?></span>
                                </div>
                            <?php endif; ?>

                            <button
                                class="aviso-btn-acao aviso-btn-desfazer"
                                data-chave="<?php echo htmlspecialchars($aviso['chave']); ?>"
                                data-acao="marcar_nao_lido"
                                type="button">
                                Marcar como não lida
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function esc(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function atualizarBadges() {
    const total = document.querySelectorAll('#lista-nao-lidos .aviso-item').length;
    const badge = document.querySelector('.aviso-count-badge');
    if (badge) { badge.textContent = total; badge.style.display = total > 0 ? '' : 'none'; }
    const sb = document.querySelector('.nav-item[href="/avisos.php"] .nav-badge');
    if (sb) { sb.textContent = total > 0 ? total : ''; sb.style.display = total > 0 ? '' : 'none'; }
    if (typeof window.jtroRecarregarAvisos === 'function') window.jtroRecarregarAvisos();
}

function registrarBotoes(container) {
    container.querySelectorAll('.aviso-btn-acao').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const chave       = this.dataset.chave;
            const acao        = this.dataset.acao;
            const item        = this.closest('.aviso-item');
            const isMarcarLido = acao === 'marcar_lido';

            fetch('/avisos_json.php', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body:    JSON.stringify({ acao: acao, chave: chave })
            }).then(function() {
                item.style.transition = 'opacity 0.2s, transform 0.2s';
                item.style.opacity    = '0';
                item.style.transform  = 'translateX(8px)';

                setTimeout(function() {
                    item.remove();

                    if (isMarcarLido) {
                        // Move para coluna lidas
                        let listaLidos = document.getElementById('lista-lidos');

                        // Se ainda não existe o wrapper (estava vazio), cria
                        if (!listaLidos) {
                            const colLidos = document.getElementById('col-lidos');
                            if (colLidos) {
                                colLidos.innerHTML = '<div class="avisos-lidos-scroll" id="lista-lidos"></div>';
                                listaLidos = document.getElementById('lista-lidos');
                            }
                        }

                        if (listaLidos) {
                            // Remove placeholder "nenhuma lida" se existir
                            listaLidos.querySelectorAll('p').forEach(p => p.remove());

                            const titulo  = item.querySelector('.aviso-item-titulo')?.textContent.trim() ?? '';
                            const detalhe = item.querySelector('.aviso-item-detalhe span')?.textContent.trim() ?? '';
                            const acaoLink = item.querySelector('.aviso-link-acao')?.outerHTML ?? '';

                            const novoItem = document.createElement('div');
                            novoItem.className      = 'aviso-item aviso-lido';
                            novoItem.dataset.chave  = chave;
                            novoItem.style.opacity  = '0';
                            novoItem.style.transform = 'translateX(-8px)';
                            novoItem.innerHTML =
                                '<div class="aviso-item-titulo aviso-titulo-lido">' + esc(titulo) + '</div>' +
                                '<div class="aviso-item-detalhe"><span>' + esc(detalhe) + '</span>' + acaoLink + '</div>' +
                                '<button class="aviso-btn-acao aviso-btn-desfazer" ' +
                                    'data-chave="' + esc(chave) + '" data-acao="marcar_nao_lido" type="button">' +
                                    'Marcar como não lida</button>';

                            listaLidos.prepend(novoItem);
                            registrarBotoes(novoItem);

                            requestAnimationFrame(function() {
                                novoItem.style.transition = 'opacity 0.2s, transform 0.2s';
                                novoItem.style.opacity    = '1';
                                novoItem.style.transform  = 'translateX(0)';
                            });
                        }

                        // Se não há mais não lidas, mostra placeholder
                        const listaNL = document.getElementById('lista-nao-lidos');
                        if (listaNL && listaNL.querySelectorAll('.aviso-item').length === 0) {
                            listaNL.innerHTML = '<p style="color:var(--color-text-muted);font-size:13px;">Nenhuma notificação não lida.</p>';
                        }

                    } else {
                        // Voltou para não lida: recarrega a página para reconstruir o card completo
                        window.location.reload();
                    }

                    atualizarBadges();
                }, 200);
            });
        });
    });
}

// Registra botões em todos os itens existentes ao carregar
registrarBotoes(document);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
