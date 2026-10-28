<?php
// Cartão escuro com a próxima reunião de um GF (Tela Inicial do admin-líder e do líder).
// Espera $gf: grupo_id, nome, proxima (data, rotulo, dia_abreviado, mes_abreviado, data_extenso) ou null,
// horario, local_padrao, membros, ultima_reuniao_id, ultima_reuniao_data.
// $tituloTag opcional ('h2' por padrão; 'h3' quando o cartão fica dentro de uma seção com título).
$tituloTagGf = in_array($tituloTag ?? 'h2', ['h2', 'h3'], true) ? ($tituloTag ?? 'h2') : 'h2';
$proximaGf   = $gf['proxima'];
$ehHojeGf    = $proximaGf !== null && $proximaGf['rotulo'] === 'Hoje';
$tituloIdGf  = 'heroGf' . (int) $gf['grupo_id'];
?>
<section class="ui-hero ui-hero--coluna" aria-labelledby="<?php echo $tituloIdGf; ?>">
  <svg class="ui-hero-marca" viewBox="0 0 180 180" fill="none" stroke="#FFFFFF" stroke-width="1.5" aria-hidden="true" style="right:-50px; top:-70px; bottom:auto; width:200px; height:200px"><use href="#i-marca"/></svg>
  <div class="ui-hero-topo">
    <span class="ui-hero-rotulo">Próxima reunião</span>
    <?php if ($proximaGf !== null): ?>
      <span class="ui-hero-chip"><?php echo htmlspecialchars($proximaGf['rotulo']); ?></span>
    <?php endif; ?>
  </div>
  <div class="ui-hero-linha">
    <?php if ($proximaGf !== null): ?>
      <div class="ui-data-bloco" aria-hidden="true"><small><?php echo htmlspecialchars($proximaGf['dia_abreviado']); ?></small><strong><?php echo $proximaGf['data']->format('j'); ?></strong><small><?php echo htmlspecialchars($proximaGf['mes_abreviado']); ?></small></div>
    <?php endif; ?>
    <div class="ui-hero-corpo">
      <<?php echo $tituloTagGf; ?> class="ui-hero-titulo" id="<?php echo $tituloIdGf; ?>"><?php echo htmlspecialchars(rotuloGf($gf['nome'])); ?></<?php echo $tituloTagGf; ?>>
      <?php if ($proximaGf !== null): ?>
        <span class="ui-sr-only"><?php echo htmlspecialchars($proximaGf['data_extenso']); ?></span>
      <?php endif; ?>
      <div class="ui-hero-meta">
        <?php if ($gf['horario'] !== ''): ?>
          <span><svg class="ui-icon ui-icon--xs"><use href="#i-relogio"/></svg><?php echo htmlspecialchars($gf['horario']); ?></span>
        <?php endif; ?>
        <span><svg class="ui-icon ui-icon--xs"><use href="#i-pessoas"/></svg><?php echo (int) $gf['membros']; ?> <?php echo (int) $gf['membros'] === 1 ? 'membro' : 'membros'; ?></span>
        <?php if ($gf['local_padrao'] !== ''): ?>
          <span><svg class="ui-icon ui-icon--xs"><use href="#i-local"/></svg><?php echo htmlspecialchars($gf['local_padrao']); ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="ui-hero-acoes">
    <?php if ($ehHojeGf): ?>
      <a class="ui-btn ui-btn--ouro" href="/presencas.php?grupo_id=<?php echo (int) $gf['grupo_id']; ?>&amp;data=<?php echo $proximaGf['data']->format('Y-m-d'); ?>">Registrar presença</a>
    <?php else: ?>
      <a class="ui-btn ui-btn--ouro" href="/diagnostico_gf.php?grupo_id=<?php echo (int) $gf['grupo_id']; ?>">Ver meu GF</a>
    <?php endif; ?>
    <?php if ($gf['ultima_reuniao_id'] !== null): ?>
      <a class="ui-btn ui-btn--vidro ui-btn--icone" href="/pedidos_oracao.php?reuniao_id=<?php echo (int) $gf['ultima_reuniao_id']; ?>" aria-label="Pedidos de oração da última reunião (<?php echo date('d/m', strtotime((string) $gf['ultima_reuniao_data'])); ?>)"><svg class="ui-icon"><use href="#i-oracao"/></svg></a>
    <?php endif; ?>
  </div>
</section>
