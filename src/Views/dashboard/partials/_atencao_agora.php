<?php
// Espera $painel['alertas'] (gfs, faltas, padrao: total + até 5 itens não lidos) e $classeColunaAtencao.
$alertas = $painel['alertas'];

// Rodapé "Ver as N … em Notificações" quando a aba tem mais itens do que a lista mostra.
// O número usa data-ui-contador para acompanhar o "Marcar como lido".
$rodapeAlertas = function (string $painelId, int $total, string $artigo, string $plural): void {
    if ($total <= 5) {
        return;
    }
    echo '<a class="ui-rodape-card" href="/avisos.php">Ver ' . $artigo . ' <span data-ui-contador="' . $painelId . '">' . $total . '</span> '
        . htmlspecialchars($plural) . ' em Notificações</a>';
};
// Estado vazio: texto do protótipo; se a aba tem mais de 5, a lista pode esvaziar
// antes de todos serem lidos, então o texto aponta para Notificações.
$vazioAlertas = function (int $total, string $textoVazio): void {
    $texto = $total > 5 ? 'Os demais alertas estão em Notificações.' : $textoVazio;
    echo '<p class="ui-vazio" data-ui-vazio' . ($total > 0 ? ' hidden' : '') . '>' . htmlspecialchars($texto) . '</p>';
};
?>
<section class="<?php echo $classeColunaAtencao; ?>ui-card ui-entra" style="--i:3" aria-labelledby="tituloAtencao">
  <div class="ui-card-head">
    <h2 id="tituloAtencao">Atenção agora</h2>
    <div class="ui-abas" role="tablist" aria-label="Tipo de alerta" data-ui-abas>
      <button class="ui-aba" type="button" role="tab" id="abaGfs" aria-controls="painelGfs" aria-selected="true">
        <span class="ui-aba-rotulo-longo">GFs alarmantes</span><span class="ui-aba-rotulo-curto">GFs</span>
        <span class="ui-aba-n" data-ui-contador="painelGfs"><?php echo (int) $alertas['gfs']['total']; ?></span>
      </button>
      <button class="ui-aba" type="button" role="tab" id="abaFaltas" aria-controls="painelFaltas" aria-selected="false" tabindex="-1">
        <span class="ui-aba-rotulo-longo">Faltas seguidas</span><span class="ui-aba-rotulo-curto">Pessoas</span>
        <span class="ui-aba-n" data-ui-contador="painelFaltas"><?php echo (int) $alertas['faltas']['total']; ?></span>
      </button>
      <button class="ui-aba" type="button" role="tab" id="abaPadrao" aria-controls="painelPadrao" aria-selected="false" tabindex="-1">
        <span class="ui-aba-rotulo-longo">Fora do padrão</span><span class="ui-aba-rotulo-curto">Reuniões</span>
        <span class="ui-aba-n" data-ui-contador="painelPadrao"><?php echo (int) $alertas['padrao']['total']; ?></span>
      </button>
    </div>
  </div>

  <div class="ui-painel" id="painelGfs" role="tabpanel" aria-labelledby="abaGfs">
    <ul class="ui-lista">
      <?php foreach ($alertas['gfs']['itens'] as $alerta): ?>
        <?php $nomeGf = rotuloGf($alerta['nome']); ?>
        <li><div class="ui-linha">
          <span class="ui-linha-icone ui-tom--alarmante"><svg class="ui-icon"><use href="#i-alerta"/></svg></span>
          <a class="ui-linha-texto" href="/diagnostico_gf.php?grupo_id=<?php echo (int) $alerta['grupo_id']; ?>"><span class="ui-linha-titulo"><?php echo htmlspecialchars($nomeGf); ?></span><span class="ui-linha-sub">Frequência <?php echo htmlspecialchars(formatarPercentualBr($alerta['percentual'])); ?> · Alarmante</span></a>
          <button class="ui-iconbtn" type="button" data-ui-marcar-lido="<?php echo htmlspecialchars($alerta['chave']); ?>" aria-label="Marcar <?php echo htmlspecialchars($nomeGf); ?> como lido"><svg class="ui-icon"><use href="#i-check"/></svg></button>
        </div></li>
      <?php endforeach; ?>
    </ul>
    <?php $vazioAlertas((int) $alertas['gfs']['total'], 'Nenhum GF em situação alarmante.'); ?>
    <?php $rodapeAlertas('painelGfs', (int) $alertas['gfs']['total'], 'os', 'GFs'); ?>
  </div>

  <div class="ui-painel" id="painelFaltas" role="tabpanel" aria-labelledby="abaFaltas" hidden>
    <ul class="ui-lista">
      <?php foreach ($alertas['faltas']['itens'] as $alerta): ?>
        <li><div class="ui-linha">
          <span class="ui-linha-icone ui-tom--atencao"><svg class="ui-icon"><use href="#i-pessoa"/></svg></span>
          <a class="ui-linha-texto" href="/diagnostico_gf.php?grupo_id=<?php echo (int) $alerta['grupo_id']; ?>&amp;membro_id=<?php echo (int) $alerta['pessoa_id']; ?>"><span class="ui-linha-titulo"><?php echo htmlspecialchars($alerta['nome']); ?></span><span class="ui-linha-sub"><?php echo (int) $alerta['faltas']; ?> faltas seguidas · <?php echo htmlspecialchars(rotuloGf($alerta['grupo_nome'])); ?></span></a>
          <button class="ui-iconbtn" type="button" data-ui-marcar-lido="<?php echo htmlspecialchars($alerta['chave']); ?>" aria-label="Marcar <?php echo htmlspecialchars($alerta['nome']); ?> como lido"><svg class="ui-icon"><use href="#i-check"/></svg></button>
        </div></li>
      <?php endforeach; ?>
    </ul>
    <?php $vazioAlertas((int) $alertas['faltas']['total'], 'Ninguém com faltas seguidas.'); ?>
    <?php $rodapeAlertas('painelFaltas', (int) $alertas['faltas']['total'], 'as', 'pessoas'); ?>
  </div>

  <div class="ui-painel" id="painelPadrao" role="tabpanel" aria-labelledby="abaPadrao" hidden>
    <ul class="ui-lista">
      <?php foreach ($alertas['padrao']['itens'] as $alerta): ?>
        <?php $nomeGf = rotuloGf($alerta['grupo_nome']); ?>
        <li><div class="ui-linha">
          <span class="ui-linha-icone ui-tom--info"><svg class="ui-icon"><use href="#i-reuniao-alerta"/></svg></span>
          <a class="ui-linha-texto" href="/reuniao_visualizar.php?id=<?php echo (int) $alerta['reuniao_id']; ?>"><span class="ui-linha-titulo"><?php echo htmlspecialchars($nomeGf); ?> · <?php echo date('d/m', strtotime($alerta['data'])); ?></span><span class="ui-linha-sub">Motivo: <?php echo htmlspecialchars($alerta['motivo']); ?></span></a>
          <button class="ui-iconbtn" type="button" data-ui-marcar-lido="<?php echo htmlspecialchars($alerta['chave']); ?>" aria-label="Marcar reunião de <?php echo htmlspecialchars($nomeGf); ?> como lida"><svg class="ui-icon"><use href="#i-check"/></svg></button>
        </div></li>
      <?php endforeach; ?>
    </ul>
    <?php $vazioAlertas((int) $alertas['padrao']['total'], 'Nenhuma reunião fora do padrão.'); ?>
    <?php $rodapeAlertas('painelPadrao', (int) $alertas['padrao']['total'], 'as', 'reuniões'); ?>
  </div>
</section>
