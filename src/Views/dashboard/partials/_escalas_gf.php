<?php
// Escalas de um GF do líder (só as que existirem). Espera $gfPainel (proxima, escalas),
// $variosGfs (nome do GF no subtítulo) e $indiceBloco (cascata).
$escalasGf      = $gfPainel['escalas'];
$tituloEscalaId = 'tituloEscalas' . (int) $gfPainel['proxima']['grupo_id'];
?>
<section class="ui-secao ui-entra" style="--i:<?php echo (int) $indiceBloco; ?>" aria-labelledby="<?php echo $tituloEscalaId; ?>">
  <div class="ui-secao-head">
    <h2 id="<?php echo $tituloEscalaId; ?>">Escalas do GF</h2>
    <?php if ($variosGfs): ?>
      <small><?php echo htmlspecialchars(rotuloGf($gfPainel['proxima']['nome'])); ?></small>
    <?php endif; ?>
  </div>
  <div class="ui-faixa">
    <?php if (isset($escalasGf['celeiro'])): ?>
      <div class="ui-bloco"><svg class="ui-icon"><use href="#i-celeiro"/></svg><small>Celeiro</small><strong><?php echo htmlspecialchars($escalasGf['celeiro']); ?></strong></div>
    <?php endif; ?>
    <?php if (isset($escalasGf['oracao'])): ?>
      <div class="ui-bloco"><svg class="ui-icon"><use href="#i-oracao"/></svg><small>Escala de oração</small><strong><?php echo (int) $escalasGf['oracao']['domingo']; ?>º domingo · <?php echo $escalasGf['oracao']['data']->format('d/m'); ?></strong></div>
    <?php endif; ?>
    <?php if (isset($escalasGf['cantina'])): ?>
      <div class="ui-bloco"><svg class="ui-icon"><use href="#i-cantina"/></svg><small>Próxima cantina</small><strong><?php echo htmlspecialchars(formatarDataBr($escalasGf['cantina'])); ?></strong></div>
    <?php endif; ?>
  </div>
</section>
