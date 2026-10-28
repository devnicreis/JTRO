<?php
// Espera $painel['ultimas'] (até 4 reuniões finalizadas dos GFs do líder) e $variosGfs (nome do GF no subtítulo).
?>
<section class="ui-secao ui-entra" style="--i:5" aria-labelledby="tituloUltimas">
  <div class="ui-secao-head"><h2 id="tituloUltimas">Últimas reuniões</h2><a class="ui-link" href="/presencas.php">Ver todas</a></div>
  <div class="ui-card">
    <?php if (empty($painel['ultimas'])): ?>
      <p class="ui-vazio">Nenhuma reunião registrada ainda.</p>
    <?php else: ?>
      <ul class="ui-lista">
        <?php foreach ($painel['ultimas'] as $ultima): ?>
          <?php
          $dataCurtaUltima = $ultima['data']->format('d/m');
          $rotuloOracao = 'Pedidos de oração da reunião de ' . $dataCurtaUltima . ($variosGfs ? ' do ' . rotuloGf($ultima['grupo_nome']) : '');
          ?>
          <li><div class="ui-linha" style="padding-right:8px">
            <span class="ui-dia"><strong><?php echo $ultima['data']->format('d'); ?></strong><small><?php echo htmlspecialchars($ultima['mes_abreviado']); ?></small></span>
            <a class="ui-reuniao-info" href="/reuniao_visualizar.php?id=<?php echo (int) $ultima['reuniao_id']; ?>">
              <span class="ui-linha-titulo" style="font-size:13px"><?php echo (int) $ultima['presentes']; ?> de <?php echo (int) $ultima['registros']; ?> presentes</span>
              <?php if ($variosGfs): ?>
                <span class="ui-linha-sub"><?php echo htmlspecialchars(rotuloGf($ultima['grupo_nome'])); ?></span>
              <?php endif; ?>
              <span class="ui-barra ui-barra--<?php echo $ultima['classificacao']['codigo']; ?>"><span style="width:<?php echo (int) $ultima['percentual']; ?>%"></span></span>
            </a>
            <a class="ui-iconbtn" href="/pedidos_oracao.php?reuniao_id=<?php echo (int) $ultima['reuniao_id']; ?>" aria-label="<?php echo htmlspecialchars($rotuloOracao); ?>"><svg class="ui-icon"><use href="#i-oracao"/></svg></a>
          </div></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
