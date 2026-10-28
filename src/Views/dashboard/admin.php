<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="ui-page">

  <header class="ui-pagehead ui-entra" style="--i:0">
    <div>
      <span class="ui-pagehead-data"><?php echo htmlspecialchars($painel['data_extenso']); ?></span>
      <h1><?php echo htmlspecialchars($painel['saudacao']); ?></h1>
    </div>
    <div class="ui-pagehead-acoes">
      <a class="ui-btn ui-btn--contorno" href="/grupos_familiares.php"><svg class="ui-icon ui-icon--sm"><use href="#i-mais-sinal"/></svg>Novo GF</a>
      <a class="ui-btn ui-btn--primario" href="/pessoas.php"><svg class="ui-icon ui-icon--sm"><use href="#i-mais-sinal"/></svg>Nova pessoa</a>
    </div>
  </header>

  <?php if ($mensagem !== ''): ?>
    <div class="mensagem" role="status"><?php echo htmlspecialchars($mensagem); ?></div>
  <?php endif; ?>

  <!-- ====== DESTAQUE + INDICADORES ====== -->
  <?php require __DIR__ . '/partials/_destaque_presenca.php'; ?>

  <!-- ====== ATENÇÃO AGORA + PRÓXIMAS REUNIÕES ====== -->
  <div class="ui-grid">
    <?php if (!empty($painel['meus_gfs'])): ?>
      <div class="ui-col-3" style="display:flex; flex-direction:column; gap:22px">
        <?php $classeColunaAtencao = ''; require __DIR__ . '/partials/_atencao_agora.php'; ?>

        <section class="ui-secao ui-entra" style="--i:4" aria-labelledby="tituloMeusGfs">
          <div class="ui-secao-head"><h2 id="tituloMeusGfs">Meus GFs</h2></div>
          <div class="ui-carrossel<?php echo count($painel['meus_gfs']) > 1 ? ' ui-carrossel--multi' : ''; ?>" aria-label="Próximas reuniões dos meus GFs">
            <?php foreach ($painel['meus_gfs'] as $gf): ?>
              <?php $tituloTag = 'h3'; require __DIR__ . '/partials/_proxima_reuniao_gf.php'; ?>
            <?php endforeach; ?>
          </div>
        </section>
      </div>
      <?php $indiceProximas = 5; require __DIR__ . '/partials/_proximas_reunioes.php'; ?>
    <?php else: ?>
      <?php $classeColunaAtencao = 'ui-col-3 '; require __DIR__ . '/partials/_atencao_agora.php'; ?>
      <?php $indiceProximas = 4; require __DIR__ . '/partials/_proximas_reunioes.php'; ?>
    <?php endif; ?>
  </div>

  <?php $indiceSaude = $indiceProximas + 1; require __DIR__ . '/partials/_saude_gfs.php'; ?>

</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
