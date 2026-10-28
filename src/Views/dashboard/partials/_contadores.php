<?php
// Espera $painel['contadores']. Sem tooltip de hover: cada número leva à lista correspondente.
$cartoesContadores = [
    ['href' => '/pessoas_cadastradas.php',           'icone' => 'i-pessoa',  'rotulo' => 'Pessoas ativas',       'valor' => $painel['contadores']['pessoas']],
    ['href' => '/grupos_familiares_cadastrados.php', 'icone' => 'i-pessoas', 'rotulo' => 'GFs ativos',           'valor' => $painel['contadores']['gfs']],
    ['href' => '/grupos_familiares_cadastrados.php', 'icone' => 'i-estrela', 'rotulo' => 'Líderes ativos',       'valor' => $painel['contadores']['lideres']],
    ['href' => '/presencas.php',                     'icone' => 'i-reuniao', 'rotulo' => 'Reuniões registradas', 'valor' => $painel['contadores']['reunioes']],
];
?>
<section class="ui-col-3 ui-kpis ui-entra" style="--i:2" aria-label="Números da igreja">
  <?php foreach ($cartoesContadores as $cartao): ?>
    <a class="ui-kpi" href="<?php echo $cartao['href']; ?>">
      <span class="ui-kpi-icone"><svg class="ui-icon"><use href="#<?php echo $cartao['icone']; ?>"/></svg></span>
      <span class="ui-kpi-texto"><span class="ui-kpi-rotulo"><?php echo htmlspecialchars($cartao['rotulo']); ?></span><span class="ui-kpi-valor" data-ui-contar="<?php echo (int) $cartao['valor']; ?>"><?php echo (int) $cartao['valor']; ?></span></span>
      <svg class="ui-icon ui-icon--sm" style="color:var(--ui-muted)"><use href="#i-chevron"/></svg>
    </a>
  <?php endforeach; ?>
</section>
