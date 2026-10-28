<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle ?? 'JTRO'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <script>
        // Tema antes de qualquer CSS (evita piscar claro). Escolha do aparelho em
        // localStorage 'jtro-tema' ('light' | 'dark'); sem escolha, segue o sistema.
        (function () {
            var sistema = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
            window.jtroAplicarTema = function () {
                var salvo = null;
                try { salvo = localStorage.getItem('jtro-tema'); } catch (e) { /* sem armazenamento */ }
                var escuro = salvo === 'dark' || (salvo !== 'light' && !!sistema && sistema.matches);
                if (escuro) document.documentElement.setAttribute('data-theme', 'dark');
                else document.documentElement.removeAttribute('data-theme');
            };
            window.jtroAplicarTema();
            if (sistema && sistema.addEventListener) sistema.addEventListener('change', window.jtroAplicarTema);
            window.addEventListener('storage', function (e) { if (e.key === 'jtro-tema') window.jtroAplicarTema(); });
        })();
    </script>
    <meta name="theme-color" content="#092D53">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="JTRO">
    <?php echo Auth::csrfMetaTag(); ?>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/icons/pwa-192.png">
    <link rel="apple-touch-icon" href="/assets/icons/pwa-192.png">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/jtro-ui.css">
</head>

<body class="ui-body">
    <svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">
        <symbol id="i-inicio" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/></symbol>
        <symbol id="i-pessoa" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></symbol>
        <symbol id="i-pessoas" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6.5 6.5-6.5s6.5 2.9 6.5 6.5"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 13.8c2.1.8 3.5 3 3.5 6.2"/></symbol>
        <symbol id="i-reuniao" viewBox="0 0 24 24"><rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M8 2.5v4M16 2.5v4M3 9.5h18"/><path d="m9 15 2 2 4-4"/></symbol>
        <symbol id="i-reuniao-alerta" viewBox="0 0 24 24"><rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M8 2.5v4M16 2.5v4M3 9.5h18"/><path d="M12 13v3M12 18.5h.01"/></symbol>
        <symbol id="i-agenda" viewBox="0 0 24 24"><rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M8 2.5v4M16 2.5v4M3 9.5h18"/><path d="M7.5 13.5h.01M12 13.5h.01M16.5 13.5h.01M7.5 17h.01M12 17h.01"/></symbol>
        <symbol id="i-carta" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/></symbol>
        <symbol id="i-sino" viewBox="0 0 24 24"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></symbol>
        <symbol id="i-mais" viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2"/></symbol>
        <symbol id="i-diagnostico" viewBox="0 0 24 24"><path d="M3 12h4l3-7 4 14 3-7h4"/></symbol>
        <symbol id="i-relatorio" viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 17v-5M12 17V7M18 17v-8"/></symbol>
        <symbol id="i-chamado" viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-4.9A8 8 0 1 1 21 12z"/></symbol>
        <symbol id="i-auditoria" viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 4.5 3.4 8.2 8 9 4.6-.8 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/></symbol>
        <symbol id="i-chevron" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></symbol>
        <symbol id="i-alerta" viewBox="0 0 24 24"><path d="M10.3 3.9 2.4 17.6A2 2 0 0 0 4.1 20.6h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></symbol>
        <symbol id="i-mais-sinal" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
        <symbol id="i-sair" viewBox="0 0 24 24"><path d="M15 12H4M8 8l-4 4 4 4"/><path d="M10 4h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-8"/></symbol>
        <symbol id="i-estrela" viewBox="0 0 24 24"><path d="m12 3 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z"/></symbol>
        <symbol id="i-lua" viewBox="0 0 24 24"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/></symbol>
        <symbol id="i-fechar" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></symbol>
        <symbol id="i-marca" viewBox="0 0 180 180"><circle cx="70" cy="70" r="56"/><circle cx="110" cy="70" r="56"/><circle cx="90" cy="105" r="56"/></symbol>
        <symbol id="i-relogio" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
        <symbol id="i-local" viewBox="0 0 24 24"><path d="M12 21s-7-6.1-7-11.5a7 7 0 1 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></symbol>
        <symbol id="i-oracao" viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-4.9A8 8 0 1 1 21 12z"/><path d="M12 15.5s-3-1.8-3-3.7a1.6 1.6 0 0 1 3-.8 1.6 1.6 0 0 1 3 .8c0 1.9-3 3.7-3 3.7z"/></symbol>
        <symbol id="i-celeiro" viewBox="0 0 24 24"><path d="M4 10h16l-1.5 8.6a2 2 0 0 1-2 1.7h-9a2 2 0 0 1-2-1.7z"/><path d="M8.5 10 11 4.5M15.5 10 13 4.5"/></symbol>
        <symbol id="i-cantina" viewBox="0 0 24 24"><path d="M4 8h13v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5z"/><path d="M17 10h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M8 2.5V5M12 2.5V5"/></symbol>
    </svg>

    <?php
    $paginaAtual = basename($_SERVER['PHP_SELF'], '.php');

    if (empty($usuario)) {
        $usuario = Auth::usuario() ?? [];
    }

    $isAdmin = Auth::isAdmin();

    // Também usada pelo footer.php (barra inferior), que é incluído no mesmo escopo.
    function uiAtual(bool $ativo): string
    {
        return $ativo ? ' aria-current="page"' : '';
    }

    function uiNavItem(string $href, string $label, string $icone, bool $ativo, string $badgeHtml = ''): string
    {
        return '<a class="ui-nav-item" href="' . $href . '"' . uiAtual($ativo) . '><svg class="ui-icon"><use href="#i-' . $icone . '"/></svg><span>' . $label . '</span>' . $badgeHtml . '</a>';
    }

    $iconeChevron = '<svg class="ui-icon ui-icon--sm ui-chevron"><use href="#i-chevron"/></svg>';

    $pessoasPaginas = [
        'pessoas',
        'pessoas_cadastradas',
        'pessoas_editar',
        'pessoas_desativar',
        'pessoas_reativar',
        'pessoas_integracao',
    ];
    $pessoasAberto = in_array($paginaAtual, $pessoasPaginas, true);

    $gruposPaginas = [
        'grupos_familiares',
        'grupos_familiares_cadastrados',
        'grupos_familiares_editar',
        'grupos_familiares_desativar',
        'grupos_familiares_reativar',
        'diagnostico_gf',
        'cantina',
    ];
    $gruposAberto = in_array($paginaAtual, $gruposPaginas, true);

    $presencasPaginas = ['presencas', 'retiro_integracao'];
    $presencasAberto = in_array($paginaAtual, $presencasPaginas, true);

    $nomeUsuario = $usuario['nome'] ?? 'Usuário';
    $perfilUsuario = $isAdmin ? 'Administrador' : 'Líder de GF';

    $partes = preg_split('/\s+/', trim($nomeUsuario));
    $primeiraInicial = !empty($partes[0]) ? mb_substr($partes[0], 0, 1) : 'U';
    $segundaInicial = isset($partes[1]) ? mb_substr($partes[1], 0, 1) : '';
    $iniciais = strtoupper($primeiraInicial . $segundaInicial);

    require_once __DIR__ . '/../../Repositories/PresencaRepository.php';
    require_once __DIR__ . '/../../Repositories/AvisoRepository.php';

    $presencaRepoHeader = new PresencaRepository();
    $avisoRepoHeader = new AvisoRepository();
    $avisoRepoHeader->sincronizarAvisosAniversarioDoDia();
    $avisoRepoHeader->sincronizarAvisosCantina();
    $usuarioIdHeader = (int) ($usuario['id'] ?? 0);
    $gruposIntegracaoHeader = $isAdmin
        ? $presencaRepoHeader->listarGruposIntegracao()
        : $presencaRepoHeader->listarGruposIntegracaoPorLider($usuarioIdHeader);
    $temAcessoRetiroIntegracao = count($gruposIntegracaoHeader) > 0;

    if (!isset($totalAvisos)) {
        $chavesLidasHeader = $usuarioIdHeader > 0 ? $avisoRepoHeader->listarChavesLidas($usuarioIdHeader) : [];
        $chavesLidasMapHeader = array_fill_keys($chavesLidasHeader, true);
        $totalAvisos = 0;

        if ($isAdmin) {
            $gruposAlarmantesHeader = $presencaRepoHeader->buscarGruposAlarmantes();
            $membrosFaltososHeader = $presencaRepoHeader->buscarMembrosComFaltasConsecutivasGerais();
            $reunioesForaDoPadraoHeader = $presencaRepoHeader->buscarReunioesForaDoPadrao();
        } else {
            $gruposAlarmantesHeader = $presencaRepoHeader->buscarGruposAlarmantesDoLider($usuarioIdHeader);
            $membrosFaltososHeader = $presencaRepoHeader->buscarMembrosComFaltasConsecutivasDoLider($usuarioIdHeader);
            $reunioesForaDoPadraoHeader = $presencaRepoHeader->buscarReunioesForaDoPadraoDoLider($usuarioIdHeader);
        }

        foreach ($gruposAlarmantesHeader as $g) {
            $chave = 'gf_alarmante_' . (int) $g['id'];
            if (!isset($chavesLidasMapHeader[$chave])) {
                $totalAvisos++;
            }
        }
        foreach ($membrosFaltososHeader as $m) {
            $chave = 'faltas_' . (int) ($m['grupo_id'] ?? 0) . '_' . (int) ($m['pessoa_id'] ?? 0);
            if (!isset($chavesLidasMapHeader[$chave])) {
                $totalAvisos++;
            }
        }
        foreach ($reunioesForaDoPadraoHeader as $r) {
            $chave = 'reuniao_fora_padrao_' . (int) $r['id'];
            if (!isset($chavesLidasMapHeader[$chave])) {
                $totalAvisos++;
            }
        }
        foreach ($avisoRepoHeader->listarAvisosSistema($usuarioIdHeader) as $avisoSistemaHeader) {
            $chave = $avisoSistemaHeader['chave_aviso'] ?? '';
            if ($chave !== '' && !isset($chavesLidasMapHeader[$chave])) {
                $totalAvisos++;
            }
        }
    }

    $totalCartasNaoLidas = 0;
    require_once __DIR__ . '/../../Repositories/CartaRepository.php';
    $cartaRepoHdr = new CartaRepository();
    $usuarioIdHdr = (int) ($usuario['id'] ?? 0);
    $chavesMapParaCarta = $chavesLidasMapHeader ?? array_fill_keys((new AvisoRepository())->listarChavesLidas($usuarioIdHdr), true);
    $totalCartasNaoLidas = $cartaRepoHdr->contarNaoLidasPorUsuario($usuarioIdHdr, $chavesMapParaCarta);

    $totalAvisosNav = (int) ($totalAvisos ?? 0);
    $totalCartasBadge = $totalCartasNaoLidas;
    $agendaPaginas = ['agenda', 'agenda_criar', 'agenda_editar'];
    $agendaAtivo = in_array($paginaAtual, $agendaPaginas, true);

    $badgeCartasHtml = $totalCartasBadge > 0 ? '<span class="ui-nav-badge">' . (int) $totalCartasBadge . '</span>' : '';
    $badgeAvisosHtml = $totalAvisosNav > 0 ? '<span class="ui-nav-badge" data-ui-contador="avisos" data-ui-ocultar-zero>' . $totalAvisosNav . '</span>' : '';
    ?>

    <div class="ui-overlay" data-ui-gaveta="fechar"></div>

    <aside class="ui-sidebar" id="uiSidebar" aria-label="Menu">
        <div class="ui-sidebar-topo">
            <a class="ui-brand" href="/index.php">
                <img src="/assets/icons/logo-jtro.png" alt="">
                <span><span class="ui-brand-nome">JTRO</span><span class="ui-brand-igreja">Comunhão Cristã Abba FRG</span></span>
            </a>
            <button class="ui-iconbtn ui-gaveta-fechar" type="button" data-ui-gaveta="fechar" aria-label="Fechar menu"><svg class="ui-icon"><use href="#i-fechar"/></svg></button>
        </div>

        <nav class="ui-nav" aria-label="Navegação principal">
            <?php echo uiNavItem('/index.php', 'Início', 'inicio', $paginaAtual === 'index'); ?>

            <div class="ui-nav-secao">Cuidado</div>
            <?php if ($isAdmin): ?>
                <details class="ui-nav-grupo"<?php echo $pessoasAberto ? ' open' : ''; ?>>
                    <summary class="ui-nav-item"><svg class="ui-icon"><use href="#i-pessoa"/></svg><span>Pessoas</span><?php echo $iconeChevron; ?></summary>
                    <div class="ui-nav-sub">
                        <a href="/pessoas.php"<?php echo uiAtual($paginaAtual === 'pessoas'); ?>>Cadastrar nova pessoa</a>
                        <a href="/pessoas_cadastradas.php"<?php echo uiAtual($paginaAtual === 'pessoas_cadastradas'); ?>>Pessoas cadastradas</a>
                    </div>
                </details>
                <details class="ui-nav-grupo"<?php echo $gruposAberto ? ' open' : ''; ?>>
                    <summary class="ui-nav-item"><svg class="ui-icon"><use href="#i-pessoas"/></svg><span>Grupos Familiares</span><?php echo $iconeChevron; ?></summary>
                    <div class="ui-nav-sub">
                        <a href="/grupos_familiares.php"<?php echo uiAtual($paginaAtual === 'grupos_familiares'); ?>>Cadastrar novo GF</a>
                        <a href="/grupos_familiares_cadastrados.php"<?php echo uiAtual($paginaAtual === 'grupos_familiares_cadastrados'); ?>>GFs cadastrados</a>
                        <a href="/cantina.php"<?php echo uiAtual($paginaAtual === 'cantina'); ?>>Cantina</a>
                        <a href="/diagnostico_gf.php"<?php echo uiAtual($paginaAtual === 'diagnostico_gf'); ?>>Diagnóstico de GFs</a>
                    </div>
                </details>
            <?php else: ?>
                <?php echo uiNavItem('/diagnostico_gf.php', 'Diagnóstico do GF', 'diagnostico', $paginaAtual === 'diagnostico_gf'); ?>
            <?php endif; ?>
            <details class="ui-nav-grupo"<?php echo $presencasAberto ? ' open' : ''; ?>>
                <summary class="ui-nav-item"><svg class="ui-icon"><use href="#i-reuniao"/></svg><span>Reuniões e Presenças</span><?php echo $iconeChevron; ?></summary>
                <div class="ui-nav-sub">
                    <a href="/presencas.php"<?php echo uiAtual($paginaAtual === 'presencas'); ?>>Reuniões</a>
                    <?php if ($temAcessoRetiroIntegracao): ?>
                        <a href="/retiro_integracao.php"<?php echo uiAtual($paginaAtual === 'retiro_integracao'); ?>>Retiro de Integração</a>
                    <?php endif; ?>
                </div>
            </details>

            <div class="ui-nav-secao">Comunicação</div>
            <?php echo uiNavItem('/agenda.php', 'Agenda', 'agenda', $agendaAtivo); ?>
            <?php echo uiNavItem('/cartas.php', 'Carta Semanal', 'carta', $paginaAtual === 'cartas', $badgeCartasHtml); ?>
            <?php echo uiNavItem('/avisos.php', 'Notificações', 'sino', $paginaAtual === 'avisos', $badgeAvisosHtml); ?>

            <?php if ($isAdmin): ?>
                <div class="ui-nav-secao">Gestão</div>
                <?php echo uiNavItem('/relatorios.php', 'Relatórios', 'relatorio', $paginaAtual === 'relatorios'); ?>
                <?php echo uiNavItem('/chamados.php', 'Chamados', 'chamado', $paginaAtual === 'chamados'); ?>
                <?php echo uiNavItem('/auditoria.php', 'Auditoria', 'auditoria', $paginaAtual === 'auditoria'); ?>
            <?php else: ?>
                <div class="ui-nav-secao">Sistema</div>
                <?php echo uiNavItem('/chamados.php', 'Chamados', 'chamado', $paginaAtual === 'chamados'); ?>
            <?php endif; ?>
        </nav>

        <a class="ui-usuario" href="/meu_perfil.php">
            <span class="ui-avatar"><?php echo htmlspecialchars($iniciais); ?></span>
            <span><span class="ui-usuario-nome"><?php echo htmlspecialchars($nomeUsuario); ?></span><span class="ui-usuario-papel"><?php echo htmlspecialchars($perfilUsuario); ?></span></span>
        </a>
        <a class="ui-sair" href="/logout.php"><svg class="ui-icon"><use href="#i-sair"/></svg>Sair</a>
    </aside>

    <header class="ui-topbar">
        <a class="ui-brand" href="/index.php">
            <img src="/assets/icons/logo-jtro.png" alt="">
            <span style="min-width:0"><span class="ui-brand-nome">JTRO</span><span class="ui-brand-igreja">Comunhão Cristã Abba FRG</span></span>
        </a>
        <a class="ui-iconbtn" href="/avisos.php" aria-label="Notificações"><svg class="ui-icon"><use href="#i-sino"/></svg><?php if ($totalAvisosNav > 0): ?><span class="ui-iconbtn-badge" data-ui-contador="avisos" data-ui-ocultar-zero><?php echo $totalAvisosNav; ?></span><?php endif; ?></a>
        <a class="ui-avatar" href="/meu_perfil.php" aria-label="Meu perfil"><?php echo htmlspecialchars($iniciais); ?></a>
    </header>

    <main class="ui-main" id="conteudo">
