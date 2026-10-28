    </main><!-- /.ui-main -->

    <?php // $isAdmin, $paginaAtual, $agendaAtivo, $totalAvisosNav, $totalCartasBadge e uiAtual() vêm do header.php (mesmo escopo). ?>
    <nav class="ui-bottomnav" aria-label="Atalhos">
        <a class="ui-bottomnav-item" href="/index.php"<?php echo uiAtual($paginaAtual === 'index'); ?>><span class="ui-bottomnav-icone"><svg class="ui-icon"><use href="#i-inicio"/></svg></span>Início</a>
        <?php if ($isAdmin): ?>
            <a class="ui-bottomnav-item" href="/pessoas_cadastradas.php"<?php echo uiAtual($paginaAtual === 'pessoas_cadastradas'); ?>><span class="ui-bottomnav-icone"><svg class="ui-icon"><use href="#i-pessoa"/></svg></span>Pessoas</a>
            <a class="ui-bottomnav-item" href="/grupos_familiares_cadastrados.php"<?php echo uiAtual($paginaAtual === 'grupos_familiares_cadastrados'); ?>><span class="ui-bottomnav-icone"><svg class="ui-icon"><use href="#i-pessoas"/></svg></span>GFs</a>
            <a class="ui-bottomnav-item" href="/presencas.php"<?php echo uiAtual($paginaAtual === 'presencas'); ?>><span class="ui-bottomnav-icone"><svg class="ui-icon"><use href="#i-reuniao"/></svg></span>Reuniões</a>
            <?php $temNovidadeMais = ($totalCartasBadge + $totalAvisosNav) > 0; ?>
            <button class="ui-bottomnav-item" type="button" data-ui-gaveta="abrir" aria-controls="uiSidebar" aria-expanded="false"><span class="ui-bottomnav-icone"><svg class="ui-icon"><use href="#i-mais"/></svg><?php if ($temNovidadeMais): ?><span class="ui-bottomnav-ponto" aria-hidden="true"></span><?php endif; ?></span>Mais<?php if ($temNovidadeMais): ?><span class="ui-sr-only">, há notificações ou cartas não lidas</span><?php endif; ?></button>
        <?php else: ?>
            <a class="ui-bottomnav-item" href="/presencas.php"<?php echo uiAtual($paginaAtual === 'presencas'); ?>><span class="ui-bottomnav-icone"><svg class="ui-icon"><use href="#i-reuniao"/></svg></span>Reuniões</a>
            <a class="ui-bottomnav-item" href="/agenda.php"<?php echo uiAtual($agendaAtivo); ?>><span class="ui-bottomnav-icone"><svg class="ui-icon"><use href="#i-agenda"/></svg></span>Agenda</a>
            <a class="ui-bottomnav-item" href="/cartas.php"<?php echo uiAtual($paginaAtual === 'cartas'); ?>><span class="ui-bottomnav-icone"><svg class="ui-icon"><use href="#i-carta"/></svg><?php if ($totalCartasBadge > 0): ?><span class="ui-bottomnav-ponto" aria-hidden="true"></span><?php endif; ?></span>Carta<?php if ($totalCartasBadge > 0): ?><span class="ui-sr-only">, <?php echo (int) $totalCartasBadge; ?> <?php echo $totalCartasBadge === 1 ? 'não lida' : 'não lidas'; ?></span><?php endif; ?></a>
            <button class="ui-bottomnav-item" type="button" data-ui-gaveta="abrir" aria-controls="uiSidebar" aria-expanded="false"><span class="ui-bottomnav-icone"><svg class="ui-icon"><use href="#i-mais"/></svg></span>Mais</button>
        <?php endif; ?>
    </nav>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/jtro-ui.js"></script>
</body>
</html>
