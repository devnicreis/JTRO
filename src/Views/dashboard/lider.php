<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<?php
$gfsLider  = $painel['gfs'];
$variosGfs = count($gfsLider) > 1;
// Coluna da direita (escalas e integração) só existe se algum GF tiver um desses blocos.
$temColunaDireita = false;
foreach ($gfsLider as $gfPainel) {
    if (!empty($gfPainel['escalas']) || !empty($gfPainel['integracao'])) {
        $temColunaDireita = true;
    }
}
?>

<div class="ui-page">

  <header class="ui-pagehead ui-entra" style="--i:0">
    <div>
      <span class="ui-pagehead-data"><?php echo htmlspecialchars($painel['data_extenso']); ?></span>
      <h1><?php echo htmlspecialchars($painel['saudacao']); ?></h1>
    </div>
  </header>

  <?php if ($mensagem !== ''): ?>
    <div class="mensagem" role="status"><?php echo htmlspecialchars($mensagem); ?></div>
  <?php endif; ?>

  <?php if (empty($gfsLider)): ?>
    <section class="ui-card ui-entra" style="--i:1" aria-label="Meus GFs">
      <p class="ui-vazio">Você ainda não está vinculado como líder de nenhum GF.</p>
    </section>
  <?php else: ?>

    <!-- ====== PRESENÇA PENDENTE ====== -->
    <?php require __DIR__ . '/partials/_pendencia_presenca.php'; ?>

    <!-- ====== PRÓXIMA REUNIÃO + FREQUÊNCIA ====== -->
    <!-- Com 2+ GFs os cartões de frequência empilham; sem stretch o destaque não estica até a altura deles. -->
    <div class="ui-grid"<?php echo $variosGfs ? '' : ' style="align-items: stretch"'; ?>>
      <div class="ui-col-3 ui-carrossel<?php echo $variosGfs ? ' ui-carrossel--multi' : ''; ?> ui-entra" style="--i:2" aria-label="Próximas reuniões dos meus GFs">
        <?php foreach ($gfsLider as $gfPainel): ?>
          <?php $gf = $gfPainel['proxima']; $tituloTag = 'h2'; require __DIR__ . '/partials/_proxima_reuniao_gf.php'; ?>
        <?php endforeach; ?>
      </div>

      <?php if ($variosGfs): ?>
        <div class="ui-col-2" style="display:flex; flex-direction:column; gap:16px">
          <?php foreach ($gfsLider as $gfPainel): ?>
            <?php $classeFrequencia = ''; require __DIR__ . '/partials/_frequencia_gf.php'; ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <?php $gfPainel = $gfsLider[0]; $classeFrequencia = 'ui-col-2 '; require __DIR__ . '/partials/_frequencia_gf.php'; ?>
      <?php endif; ?>
    </div>

    <!-- ====== CUIDADO + ÚLTIMAS REUNIÕES | ESCALAS + INTEGRAÇÃO ====== -->
    <div class="ui-grid">
      <div class="ui-col-3" style="display:flex; flex-direction:column; gap:22px">
        <?php require __DIR__ . '/partials/_precisam_cuidado.php'; ?>
        <?php require __DIR__ . '/partials/_ultimas_reunioes.php'; ?>
      </div>

      <?php if ($temColunaDireita): ?>
        <div class="ui-col-2" style="display:flex; flex-direction:column; gap:22px">
          <?php $indiceBloco = 6; ?>
          <?php foreach ($gfsLider as $gfPainel): ?>
            <?php if (!empty($gfPainel['escalas'])): ?>
              <?php require __DIR__ . '/partials/_escalas_gf.php'; $indiceBloco++; ?>
            <?php endif; ?>
            <?php if (!empty($gfPainel['integracao'])): ?>
              <?php require __DIR__ . '/partials/_integracao.php'; $indiceBloco++; ?>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  <?php endif; ?>

</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
