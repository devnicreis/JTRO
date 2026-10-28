<?php
// Espera $painel['proximas'] (dias agrupados, até 8 linhas, + excedente) e $indiceProximas (cascata).
$proximasPainel = $painel['proximas'];
?>
<section class="ui-col-2 ui-card ui-entra" style="--i:<?php echo (int) $indiceProximas; ?>" aria-labelledby="tituloProximas">
  <div class="ui-card-head" style="padding-bottom:4px">
    <h2 id="tituloProximas">Próximas reuniões</h2>
    <a class="ui-link" href="/agenda.php">Agenda</a>
  </div>
  <?php if (empty($proximasPainel['dias'])): ?>
    <p class="ui-vazio">Nenhuma reunião prevista nos próximos 7 dias.</p>
  <?php endif; ?>
  <?php foreach ($proximasPainel['dias'] as $dia): ?>
    <div class="ui-grupo-dia"><?php echo htmlspecialchars($dia['titulo']); ?></div>
    <ul class="ui-lista">
      <?php foreach ($dia['reunioes'] as $reuniao): ?>
        <li><a class="ui-linha" href="/diagnostico_gf.php?grupo_id=<?php echo (int) $reuniao['grupo_id']; ?>"><span class="ui-hora"><?php echo htmlspecialchars($reuniao['horario']); ?></span><span class="ui-linha-texto"><span class="ui-linha-titulo"><?php echo htmlspecialchars(rotuloGf($reuniao['nome'])); ?></span><span class="ui-linha-sub"><?php echo (int) $reuniao['membros']; ?> <?php echo (int) $reuniao['membros'] === 1 ? 'membro' : 'membros'; ?></span></span></a></li>
      <?php endforeach; ?>
    </ul>
  <?php endforeach; ?>
  <?php if ($proximasPainel['total_excedente'] > 0): ?>
    <a class="ui-rodape-card" href="/agenda.php"><?php echo htmlspecialchars($proximasPainel['rotulo_excedente']); ?></a>
  <?php endif; ?>
</section>
