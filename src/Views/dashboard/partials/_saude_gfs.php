<?php
// Espera $painel['saude'] (total + até 8 GFs ordenados) e $indiceSaude (cascata).
// Selo sempre com texto (e ícone, exceto "Sem registros", como no protótipo).
$saudePainel = $painel['saude'];
?>
<section class="ui-card ui-entra" style="--i:<?php echo (int) $indiceSaude; ?>" aria-labelledby="tituloSaude">
  <div class="ui-card-head">
    <div>
      <h2 id="tituloSaude">Saúde dos GFs</h2>
      <span class="ui-card-sub">Frequência geral de cada GF, da menor para a maior</span>
    </div>
    <a class="ui-link" href="/diagnostico_gf.php">Abrir diagnóstico</a>
  </div>
  <?php if (empty($saudePainel['itens'])): ?>
    <p class="ui-vazio">Nenhum GF ativo</p>
  <?php else: ?>
    <div class="ui-saude-cab" aria-hidden="true"><span>GF</span><span>Frequência</span><span>Diagnóstico</span><span>Membros</span><span>Última reunião</span></div>
    <ul class="ui-lista ui-lista--sem-borda-topo" style="border-top:1px solid var(--ui-border)">
      <?php foreach ($saudePainel['itens'] as $indice => $gf): ?>
        <?php
        $codigo   = $gf['classificacao']['codigo'];
        $neutro   = $codigo === 'neutro';
        $largura  = $neutro ? 0 : max(0, min(100, $gf['percentual']));
        $membros  = (int) $gf['membros'] . ((int) $gf['membros'] === 1 ? ' membro' : ' membros');
        $ultima   = $gf['ultima_reuniao'] !== null ? strtotime($gf['ultima_reuniao']) : null;
        $metaMobile = $membros . ' · ' . ($ultima !== null ? 'última reunião ' . date('d/m', $ultima) : 'nenhuma reunião registrada');
        ?>
        <li><a class="ui-saude-linha" href="/diagnostico_gf.php?grupo_id=<?php echo (int) $gf['grupo_id']; ?>"<?php echo $indice === 0 ? ' style="border-top:0"' : ''; ?>>
          <span class="ui-saude-nome"><?php echo htmlspecialchars(rotuloGf($gf['nome'])); ?></span>
          <span class="ui-saude-freq"><span class="ui-barra ui-barra--<?php echo $codigo; ?>"><span style="width:<?php echo $largura; ?>%"></span></span><span class="ui-saude-pct"><?php echo $neutro ? '—' : htmlspecialchars(formatarPercentualBr($gf['percentual'])); ?></span></span>
          <span class="ui-selo ui-selo--<?php echo $codigo; ?>"><?php if (iconeDiagnostico($codigo) !== null): ?><svg class="ui-icon ui-icon--xs"><use href="#<?php echo iconeDiagnostico($codigo); ?>"/></svg><?php endif; ?><?php echo htmlspecialchars($gf['classificacao']['rotulo']); ?></span>
          <span class="ui-saude-meta"><?php echo (int) $gf['membros']; ?></span><span class="ui-saude-meta"><?php echo $ultima !== null ? date('d/m/Y', $ultima) : '—'; ?></span>
          <span class="ui-saude-meta--mobile"><?php echo htmlspecialchars($metaMobile); ?></span>
        </a></li>
      <?php endforeach; ?>
    </ul>
    <a class="ui-rodape-card" href="/diagnostico_gf.php">Ver os <?php echo (int) $saudePainel['total']; ?> GFs no Diagnóstico</a>
  <?php endif; ?>
</section>
