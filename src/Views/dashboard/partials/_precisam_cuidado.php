<?php
// Espera $painel['cuidado'] (membros com 2+ faltas seguidas de todos os GFs do líder, já ordenados),
// $gfsLider e $variosGfs (nome do GF no subtítulo de cada pessoa).
// Selo: 3+ faltas em vermelho, 2 em amarelo (destaque visual apenas; a regra continua sendo 2+).
$cuidadoLista = $painel['cuidado'];
$totalCuidado = count($cuidadoLista);
$linkDiagnosticoCuidado = $variosGfs
    ? '/diagnostico_gf.php'
    : '/diagnostico_gf.php?grupo_id=' . (int) $gfsLider[0]['proxima']['grupo_id'];
?>
<section class="ui-secao ui-entra" style="--i:4" aria-labelledby="tituloCuidado">
  <div class="ui-secao-head">
    <h2 id="tituloCuidado">Precisam de cuidado</h2>
    <?php if ($totalCuidado > 0): ?>
      <small><?php echo $totalCuidado; ?> <?php echo $totalCuidado === 1 ? 'pessoa' : 'pessoas'; ?></small>
    <?php endif; ?>
  </div>
  <div class="ui-card">
    <?php if ($totalCuidado === 0): ?>
      <p class="ui-vazio">Nenhum membro com faltas seguidas.</p>
    <?php else: ?>
      <ul class="ui-lista">
        <?php foreach ($cuidadoLista as $pessoaCuidado): ?>
          <?php
          $subCuidado = $pessoaCuidado['ultima_presenca'] !== null
              ? 'Última presença: ' . date('d/m', strtotime($pessoaCuidado['ultima_presenca']))
              : 'Nenhuma presença registrada';
          if ($variosGfs) {
              $subCuidado .= ' · ' . rotuloGf($pessoaCuidado['grupo_nome']);
          }
          ?>
          <li><a class="ui-linha" href="/diagnostico_gf.php?grupo_id=<?php echo (int) $pessoaCuidado['grupo_id']; ?>&amp;membro_id=<?php echo (int) $pessoaCuidado['pessoa_id']; ?>">
            <span class="ui-avatar ui-avatar--g"><?php echo htmlspecialchars(iniciaisNome($pessoaCuidado['nome'])); ?></span>
            <span class="ui-linha-texto"><span class="ui-linha-titulo"><?php echo htmlspecialchars($pessoaCuidado['nome']); ?></span><span class="ui-linha-sub"><?php echo htmlspecialchars($subCuidado); ?></span></span>
            <span class="ui-selo ui-selo--<?php echo $pessoaCuidado['faltas'] >= 3 ? 'alarmante' : 'atencao'; ?>"><svg class="ui-icon ui-icon--xs"><use href="#i-alerta"/></svg><?php echo (int) $pessoaCuidado['faltas']; ?> faltas seguidas</span>
          </a></li>
        <?php endforeach; ?>
      </ul>
      <a class="ui-rodape-card" href="<?php echo htmlspecialchars($linkDiagnosticoCuidado); ?>"><?php echo $variosGfs ? 'Ver diagnóstico dos GFs' : 'Ver diagnóstico do GF'; ?><svg class="ui-icon ui-icon--sm"><use href="#i-chevron"/></svg></a>
    <?php endif; ?>
  </div>
</section>
