<?php
// Espera $painel['situacao'] (total, registrados, pendentes[]) e $painel['contadores'].
$situacao    = $painel['situacao'];
$totalGfs    = (int) $situacao['total'];
$registrados = (int) $situacao['registrados'];
$faltam      = $totalGfs - $registrados;
$pendentesVisiveis = array_slice($situacao['pendentes'], 0, 6);
$pendentesOcultos  = count($situacao['pendentes']) - count($pendentesVisiveis);
$rotuloGfs = fn(int $n): string => $n . ($n === 1 ? ' GF' : ' GFs');
?>
<div class="ui-grid" style="align-items: stretch">

  <?php if ($totalGfs === 0): ?>
    <section class="ui-col-2 ui-card ui-entra" style="--i:1" aria-label="Última reunião de cada GF">
      <p class="ui-vazio">Nenhum GF ativo</p>
    </section>
  <?php else: ?>
    <?php
    $dashoffset = (int) round(264 * (1 - $registrados / $totalGfs));
    $tituloDestaque = $faltam === 0
        ? 'Todos os GFs registraram a última reunião.'
        : 'Presença registrada em ' . $rotuloGfs($registrados) . '. ' . ($faltam === 1 ? 'Falta' : 'Faltam') . ' ' . $faltam . '.';
    ?>
    <section class="ui-col-2 ui-hero ui-entra" style="--i:1" aria-labelledby="heroTitulo">
      <svg class="ui-hero-marca" viewBox="0 0 180 180" fill="none" stroke="#FFFFFF" stroke-width="1.5" aria-hidden="true"><use href="#i-marca"/></svg>
      <div class="ui-anel" role="img" aria-label="<?php echo $registrados; ?> de <?php echo $rotuloGfs($totalGfs); ?> com a última reunião registrada">
        <svg viewBox="0 0 96 96" aria-hidden="true">
          <circle class="ui-anel-fundo" cx="48" cy="48" r="42"/>
          <circle class="ui-anel-valor" cx="48" cy="48" r="42" style="stroke-dashoffset: <?php echo $dashoffset; ?>"/>
        </svg>
        <div class="ui-anel-centro"><strong data-ui-contar="<?php echo $registrados; ?>"><?php echo $registrados; ?></strong><small>de <?php echo $rotuloGfs($totalGfs); ?></small></div>
      </div>
      <div class="ui-hero-corpo">
        <span class="ui-hero-rotulo">Última reunião de cada GF</span>
        <h2 class="ui-hero-titulo" id="heroTitulo"><?php echo htmlspecialchars($tituloDestaque); ?></h2>
        <div class="ui-hero-acoes">
          <?php if ($faltam > 0): ?>
            <button class="ui-btn ui-btn--ouro" type="button" data-ui-alternar="listaPendentes" aria-expanded="false" aria-controls="listaPendentes">
              <span data-ui-rotulo data-ui-rotulo-fechado="Ver pendentes" data-ui-rotulo-aberto="Ocultar pendentes">Ver pendentes</span>
              <svg class="ui-icon ui-icon--sm"><use href="#i-chevron"/></svg>
            </button>
          <?php endif; ?>
          <a class="ui-btn ui-btn--vidro" href="/carta_criar.php">Escrever carta semanal</a>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php require __DIR__ . '/_contadores.php'; ?>
</div>

<?php if ($totalGfs > 0 && $faltam > 0): ?>
  <!-- Lista de pendentes (aberta pelo botão do destaque) -->
  <section class="ui-pendentes" id="listaPendentes" hidden aria-label="GFs com presença pendente">
    <ul class="ui-lista">
      <?php foreach ($pendentesVisiveis as $pendente): ?>
        <?php
        $partesSub = [];
        if ($pendente['dia_curto'] !== null) {
            $partesSub[] = 'Reunião prevista: ' . $pendente['dia_curto'] . ($pendente['horario'] !== '' ? ' às ' . $pendente['horario'] : '');
        } else {
            $partesSub[] = 'Dia da reunião não cadastrado';
        }
        if (trim((string) $pendente['lideres']) !== '') {
            $partesSub[] = 'Líderes: ' . $pendente['lideres'];
        }
        $linkReuniao = '/presencas.php?grupo_id=' . (int) $pendente['grupo_id']
            . ($pendente['data_prevista'] !== null ? '&data=' . $pendente['data_prevista'] : '');
        ?>
        <li><div class="ui-linha ui-linha--acao-abaixo">
          <span class="ui-linha-icone ui-tom--atencao"><svg class="ui-icon"><use href="#i-reuniao-alerta"/></svg></span>
          <span class="ui-linha-texto"><span class="ui-linha-titulo"><?php echo htmlspecialchars(rotuloGf($pendente['nome'])); ?></span><span class="ui-linha-sub"><?php echo htmlspecialchars(implode(' · ', $partesSub)); ?></span></span>
          <a class="ui-btn ui-btn--contorno ui-btn--pequeno" href="<?php echo htmlspecialchars($linkReuniao); ?>">Abrir reunião</a>
        </div></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($pendentesOcultos > 0): ?>
      <a class="ui-rodape-card" href="/presencas.php">+ <?php echo $rotuloGfs($pendentesOcultos); ?> <?php echo $pendentesOcultos === 1 ? 'pendente' : 'pendentes'; ?> em Reuniões</a>
    <?php endif; ?>
  </section>
<?php endif; ?>
