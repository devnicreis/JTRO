<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <h1>Carta Semanal</h1>
    <?php if (Auth::isAdmin()): ?>
        <a href="/carta_criar.php" class="botao-link" style="margin-top:12px; display:inline-flex;">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" style="margin-right:6px;"><path d="M8 2v12M2 8h12"/></svg>
            Nova Carta
        </a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['criada'])): ?>
    <div class="mensagem">Carta criada com sucesso.</div>
<?php elseif (isset($_GET['editada'])): ?>
    <div class="mensagem">Carta atualizada com sucesso.</div>
<?php endif; ?>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if (empty($cartas)): ?>
    <div class="presencas-card" style="text-align:center; padding:40px;">
        <p style="color:var(--color-text-muted); font-size:14px;">Nenhuma carta disponível ainda.</p>
    </div>
<?php else: ?>

    <?php
    $cartaAtual   = null;
    $cartasAnt    = [];
    foreach ($cartas as $c) {
        if ($c['publicada'] && $cartaAtual === null) {
            $cartaAtual = $c;
        } else {
            $cartasAnt[] = $c;
        }
    }
    // Admin vê rascunhos na lista de anteriores também
    ?>

    <div class="presencas-layout">

        <!-- Coluna esquerda: carta mais recente -->
        <div class="presencas-coluna">
            <div class="presencas-card">
                <h2><?php echo Auth::isAdmin() ? 'Carta atual / Rascunho' : 'Nova Carta'; ?></h2>

                <?php if ($cartaAtual): ?>
                    <?php echo cartaPreview($cartaAtual, Auth::isAdmin()); ?>
                <?php elseif (Auth::isAdmin() && !empty($cartas)): ?>
                    <?php echo cartaPreview($cartas[0], true); ?>
                <?php else: ?>
                    <p style="color:var(--color-text-muted); font-size:13px;">Nenhuma carta publicada ainda.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna direita: cartas anteriores -->
        <div class="presencas-coluna">
            <div class="presencas-card">
                <h2>Cartas anteriores</h2>
                <?php
                $lista = Auth::isAdmin() ? array_slice($cartas, 1) : $cartasAnt;
                if (empty($lista)):
                ?>
                    <p style="color:var(--color-text-muted); font-size:13px;">Nenhuma carta anterior.</p>
                <?php else: ?>
                    <div class="avisos-lidos-scroll">
                        <?php foreach ($lista as $c): ?>
                            <div class="carta-item-ant">
                                <div>
                                    <div class="carta-item-data">
                                        <?php echo htmlspecialchars(formatarDataBr($c['data_carta'])); ?>
                                        <?php if (!$c['publicada']): ?>
                                            <span class="carta-badge-rascunho">Rascunho</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($c['pregacao_titulo'])): ?>
                                        <div class="carta-item-pregacao"><?php echo htmlspecialchars($c['pregacao_titulo']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <a class="btn-visualizar" href="/carta_visualizar.php?id=<?php echo $c['id']; ?>">Ver</a>
                                    <?php if (Auth::isAdmin()): ?>
                                        <a class="btn-visualizar" href="/carta_editar.php?id=<?php echo $c['id']; ?>"
                                           style="color:var(--color-amber); border-color:var(--color-amber-mid);">Editar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
<?php endif; ?>

<?php
function formatarDataBr(?string $data): string {
    if (empty($data)) return '—';
    $ts = strtotime($data);
    if ($ts === false) return $data;
    return date('d/m/Y', $ts);
}

function cartaPreview(array $carta, bool $isAdmin): string {
    $data = formatarDataBr($carta['data_carta']);
    $pub  = $carta['publicada'] ? '' : '<span class="carta-badge-rascunho">Rascunho</span>';
    $html = '<div class="carta-preview">';
    $html .= '<div class="carta-preview-data">' . htmlspecialchars($data) . ' ' . $pub . '</div>';
    if (!empty($carta['pregacao_titulo'])) {
        $html .= '<div class="carta-preview-pregacao">Pregação: <strong>' . htmlspecialchars($carta['pregacao_titulo']) . '</strong></div>';
    }
    $html .= '<div class="carta-preview-acoes">';
    $html .= '<a class="botao-link" href="/carta_visualizar.php?id=' . $carta['id'] . '">Ver carta completa</a>';
    if ($isAdmin) {
        $html .= ' <a class="botao-link botao-secundario" href="/carta_editar.php?id=' . $carta['id'] . '">Editar</a>';
        if (!$carta['publicada']) {
            $html .= '<form method="POST" action="/cartas.php" class="form-acao" style="display:inline;">
                <input type="hidden" name="acao" value="publicar">
                <input type="hidden" name="id" value="' . $carta['id'] . '">
                <button type="submit" class="btn-gf btn-gf-reativar" style="width:auto; padding:6px 14px;">Publicar</button>
            </form>';
        } else {
            $html .= '<form method="POST" action="/cartas.php" class="form-acao" style="display:inline;"
                      onsubmit="return confirm(\'Despublicar esta carta?\')">
                <input type="hidden" name="acao" value="despublicar">
                <input type="hidden" name="id" value="' . $carta['id'] . '">
                <button type="submit" class="btn-gf btn-gf-desativar" style="width:auto; padding:6px 14px;">Despublicar</button>
            </form>';
        }
    }
    $html .= '</div></div>';
    return $html;
}
?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>