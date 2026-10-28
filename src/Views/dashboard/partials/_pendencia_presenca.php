<?php
// Espera $painel['pendencias']: um aviso por GF do líder sem registro da última reunião prevista (regras 5 e 6).
?>
<?php if (!empty($painel['pendencias'])): ?>
  <div class="ui-entra" style="--i:1; display:flex; flex-direction:column; gap:10px">
    <?php foreach ($painel['pendencias'] as $pendencia): ?>
      <?php
      $linkPendencia = '/presencas.php?grupo_id=' . (int) $pendencia['grupo_id']
          . ($pendencia['data_prevista'] !== null ? '&data=' . $pendencia['data_prevista'] : '');
      $textoPendencia = rotuloGf($pendencia['nome']) . ' · '
          . ($pendencia['dia_curto'] !== null ? 'reunião de ' . $pendencia['dia_curto'] . ' sem registro' : 'dia da reunião não cadastrado');
      ?>
      <a class="ui-aviso" href="<?php echo htmlspecialchars($linkPendencia); ?>">
        <span class="ui-aviso-icone"><svg class="ui-icon"><use href="#i-reuniao-alerta"/></svg></span>
        <span><strong>Presença pendente</strong><span><?php echo htmlspecialchars($textoPendencia); ?></span></span>
        <span class="ui-aviso-acao">Registrar</span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
