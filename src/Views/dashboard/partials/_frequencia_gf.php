<?php
// Cartão de frequência de um GF do líder. Espera $gfPainel (proxima, frequencia, colunas),
// $variosGfs (nome do GF no subtítulo) e $classeFrequencia ('ui-col-2 ' quando o cartão é a própria coluna).
$freqGf       = $gfPainel['frequencia'];
$colunasGf    = $gfPainel['colunas'];
$codigoFreq   = $freqGf['classificacao']['codigo'];
$tituloFreqId = 'tituloFreq' . (int) $gfPainel['proxima']['grupo_id'];
$totalReunioesGf = (int) $freqGf['total_reunioes'];

// "7 de 8, 8 de 8 e 6 de 8"
$partesColunas = array_map(fn(array $c): string => $c['presentes'] . ' de ' . $c['registros'], $colunasGf);
$ultimaParte   = array_pop($partesColunas);
$listaColunas  = count($partesColunas) > 0 ? implode(', ', $partesColunas) . ' e ' . $ultimaParte : (string) $ultimaParte;
$rotuloColunas = count($colunasGf) === 1
    ? 'Presentes na última reunião: ' . $listaColunas
    : 'Presentes nas últimas ' . count($colunasGf) . ' reuniões: ' . $listaColunas;
?>
<section class="<?php echo $classeFrequencia; ?>ui-card ui-entra" style="--i:3" aria-labelledby="<?php echo $tituloFreqId; ?>">
  <div class="ui-freq">
    <div class="ui-freq-topo">
      <div>
        <span class="ui-card-sub" id="<?php echo $tituloFreqId; ?>" style="font-weight:600"><?php echo $variosGfs ? 'Frequência · ' . htmlspecialchars(rotuloGf($gfPainel['proxima']['nome'])) : 'Frequência do GF'; ?></span>
        <?php if ($totalReunioesGf === 0 || $codigoFreq === 'neutro'): ?>
          <div class="ui-freq-valor">—</div>
        <?php else: ?>
          <div class="ui-freq-valor" data-ui-contar="<?php echo number_format($freqGf['percentual'], 1, '.', ''); ?>" data-ui-decimais="1" data-ui-sufixo="%"><?php echo htmlspecialchars(formatarPercentualBr($freqGf['percentual'])); ?></div>
        <?php endif; ?>
        <span class="ui-card-sub"><?php
          if ($totalReunioesGf === 0) {
              echo 'Nenhuma reunião registrada';
          } else {
              echo $totalReunioesGf === 1 ? 'média da única reunião registrada' : 'média das ' . $totalReunioesGf . ' reuniões registradas';
          }
        ?></span>
      </div>
      <?php if ($totalReunioesGf > 0): ?>
        <span class="ui-selo ui-selo--<?php echo $codigoFreq; ?>"><?php if (iconeDiagnostico($codigoFreq) !== null): ?><svg class="ui-icon ui-icon--xs"><use href="#<?php echo iconeDiagnostico($codigoFreq); ?>"/></svg><?php endif; ?><?php echo htmlspecialchars($freqGf['classificacao']['rotulo']); ?></span>
      <?php endif; ?>
    </div>
    <?php if ($totalReunioesGf > 0 && !empty($colunasGf)): ?>
      <div class="ui-colunas" role="img" aria-label="<?php echo htmlspecialchars($rotuloColunas); ?>">
        <?php foreach ($colunasGf as $indiceColuna => $coluna): ?>
          <span<?php echo $coluna['baixa'] ? ' class="ui-coluna--baixa"' : ''; ?> style="--i:<?php echo $indiceColuna; ?>; height:<?php echo $coluna['percentual']; ?>%"></span>
        <?php endforeach; ?>
      </div>
      <div class="ui-colunas-legenda">
        <span><?php echo date('d/m', strtotime($colunasGf[0]['data'])); ?></span>
        <span>Presentes por reunião</span>
        <span><?php echo count($colunasGf) > 1 ? date('d/m', strtotime($colunasGf[count($colunasGf) - 1]['data'])) : ''; ?></span>
      </div>
    <?php endif; ?>
  </div>
</section>
