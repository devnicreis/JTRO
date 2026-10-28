<?php
// Membros de um GF de integração (mesma filtragem da tela antiga: sem líderes de GF/departamento).
// Espera $gfPainel (proxima, integracao), $variosGfs (nome do GF no subtítulo) e $indiceBloco (cascata).
$tituloIntegracaoId = 'tituloIntegracao' . (int) $gfPainel['proxima']['grupo_id'];
?>
<section class="ui-secao ui-entra" style="--i:<?php echo (int) $indiceBloco; ?>" aria-labelledby="<?php echo $tituloIntegracaoId; ?>">
  <div class="ui-secao-head">
    <h2 id="<?php echo $tituloIntegracaoId; ?>">Integração</h2>
    <?php if ($variosGfs): ?>
      <small><?php echo htmlspecialchars(rotuloGf($gfPainel['proxima']['nome'])); ?></small>
    <?php endif; ?>
  </div>
  <div class="ui-card">
    <ul class="ui-lista">
      <?php foreach ($gfPainel['integracao'] as $membroIntegracao): ?>
        <li><a class="ui-linha" href="/pessoas_integracao.php?id=<?php echo (int) $membroIntegracao['pessoa_id']; ?>"><span class="ui-avatar"><?php echo htmlspecialchars(iniciaisNome($membroIntegracao['nome'])); ?></span><span class="ui-linha-texto"><span class="ui-linha-titulo"><?php echo htmlspecialchars($membroIntegracao['nome']); ?></span><span class="ui-linha-sub">Aulas da integração</span></span><svg class="ui-icon ui-icon--sm" style="color:var(--ui-muted)"><use href="#i-chevron"/></svg></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
