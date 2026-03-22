<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle ?? 'JTRO'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body>

    <?php
    $paginaAtual = basename($_SERVER['PHP_SELF'], '.php');

    if (empty($usuario)) {
        $usuario = Auth::usuario() ?? [];
    }

    $isAdmin = Auth::isAdmin();

    function navItem(string $href, string $label, string $pagina, string $paginaAtual, string $icon, int $badge = 0): string
    {
        $ativo = $pagina === $paginaAtual ? ' ativo' : '';
        $badgeHtml = $badge > 0 ? '<span class="nav-badge">' . $badge . '</span>' : '';
        return '<a href="' . $href . '" class="nav-item' . $ativo . '">' . $icon . '<span>' . $label . '</span>' . $badgeHtml . '</a>';
    }

    /* Ícones */
    $iconDashboard    = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>';
    $iconPessoas      = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="5" r="3"/><path d="M2 15c0-3.3 2.7-6 6-6s6 2.7 6 6"/></svg>';
    $iconGrupos       = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="5" cy="5" r="2.5"/><circle cx="11" cy="5" r="2.5"/><path d="M1 14c0-2.8 1.8-5 4-5M15 14c0-2.8-1.8-5-4-5M5 14c0-2.2 1.3-4 3-4s3 1.8 3 4"/></svg>';
    $iconPresencas    = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="12" height="11" rx="2"/><path d="M5 1v4M11 1v4M2 7h12"/><path d="M5.5 10.5l1.5 1.5 3-3"/></svg>';
    $iconNotificacoes = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1a5 5 0 015 5c0 3 1 4 1.5 5h-13C2 10 3 9 3 6a5 5 0 015-5z"/><path d="M6.5 13a1.5 1.5 0 003 0"/></svg>';
    $iconAuditoria    = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 4h10M3 8h7M3 12h5"/></svg>';
    $iconAgenda       = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="12" height="11" rx="2"/><path d="M5 1v4M11 1v4M2 7h12"/><path d="M5 10h2M9 10h2M5 13h2"/></svg>';
    $iconCarta        = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="14" height="10" rx="2"/><path d="M1 5l7 5 7-5"/></svg>';
    $iconChevron      = '<svg class="nav-chevron" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 7l3 3 3-3"/></svg>';
    $iconSair         = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 8H2M6 5l-3 3 3 3"/><path d="M6 2h6a1 1 0 011 1v10a1 1 0 01-1 1H6"/></svg>';

    /* Páginas que deixam o submenu de Grupos Familiares aberto */
    $gruposPaginas = [
        'grupos_familiares',
        'grupos_familiares_editar',
        'grupos_familiares_desativar',
        'grupos_familiares_reativar',
        'diagnostico_gf'
    ];

    $gruposAberto = in_array($paginaAtual, $gruposPaginas, true);

    /* Dados do usuário logado */
    $nomeUsuario   = $usuario['nome'] ?? 'Usuário';
    $perfilUsuario = $isAdmin ? 'Administrador' : 'Líder';

    $partes          = preg_split('/\s+/', trim($nomeUsuario));
    $primeiraInicial = !empty($partes[0]) ? mb_substr($partes[0], 0, 1) : 'U';
    $segundaInicial  = isset($partes[1]) ? mb_substr($partes[1], 0, 1) : '';
    $iniciais        = strtoupper($primeiraInicial . $segundaInicial);

    /*
|------------------------------------------------------------
| Badge de notificações + cartas não lidas (só líderes)
|------------------------------------------------------------
*/
    if (!isset($totalAvisos)) {
        require_once __DIR__ . '/../../Repositories/PresencaRepository.php';
        require_once __DIR__ . '/../../Repositories/AvisoRepository.php';

        $presencaRepoHeader = new PresencaRepository();
        $avisoRepoHeader    = new AvisoRepository();

        $usuarioIdHeader   = (int) ($usuario['id'] ?? 0);
        $chavesLidasHeader = $usuarioIdHeader > 0
            ? $avisoRepoHeader->listarChavesLidas($usuarioIdHeader)
            : [];

        $chavesLidasMapHeader = array_fill_keys($chavesLidasHeader, true);
        $totalAvisos = 0;

        if ($isAdmin) {
            $gruposAlarmantesHeader    = $presencaRepoHeader->buscarGruposAlarmantes();
            $membrosFaltososHeader     = $presencaRepoHeader->buscarMembrosComFaltasConsecutivasGerais();
            $reunioesForaDoPadraoHeader = $presencaRepoHeader->buscarReunioesForaDoPadrao();
        } else {
            $gruposAlarmantesHeader    = $presencaRepoHeader->buscarGruposAlarmantesDoLider($usuarioIdHeader);
            $membrosFaltososHeader     = $presencaRepoHeader->buscarMembrosComFaltasConsecutivasDoLider($usuarioIdHeader);
            $reunioesForaDoPadraoHeader = $presencaRepoHeader->buscarReunioesForaDoPadraoDoLider($usuarioIdHeader);
        }

        foreach ($gruposAlarmantesHeader as $g) {
            $chave = 'gf_alarmante_' . (int) $g['id'];
            if (!isset($chavesLidasMapHeader[$chave])) $totalAvisos++;
        }
        foreach ($membrosFaltososHeader as $m) {
            $chave = 'faltas_' . (int)($m['grupo_id'] ?? 0) . '_' . (int)($m['pessoa_id'] ?? 0);
            if (!isset($chavesLidasMapHeader[$chave])) $totalAvisos++;
        }
        foreach ($reunioesForaDoPadraoHeader as $r) {
            $chave = 'reuniao_fora_padrao_' . (int) $r['id'];
            if (!isset($chavesLidasMapHeader[$chave])) $totalAvisos++;
        }
    }

    // Cartas não lidas — apenas para líderes
    $totalCartasNaoLidas = 0;
    if (!$isAdmin) {
        require_once __DIR__ . '/../../Repositories/CartaRepository.php';
        $cartaRepoHdr    = new CartaRepository();
        $usuarioIdHdr    = (int)($usuario['id'] ?? 0);
        $chavesMapParaCarta = $chavesLidasMapHeader ?? array_fill_keys(
            (new AvisoRepository())->listarChavesLidas($usuarioIdHdr),
            true
        );
        $totalCartasNaoLidas = $cartaRepoHdr->contarNaoLidasPorUsuario($usuarioIdHdr, $chavesMapParaCarta);
    }

    $totalAvisosNav  = (int)($totalAvisos ?? 0);
    $totalCartasBadge = $totalCartasNaoLidas;
    $agendaPaginas    = ['agenda', 'agenda_criar', 'agenda_editar'];
    ?>

    <div class="jtro-layout">
        <aside class="jtro-sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-topo">
                    <img src="/assets/icons/logo-com-nome-lado.png" alt="Logo JTRO" class="sidebar-logo-img" onerror="this.style.display='none'">
                </div>
                <span class="sidebar-logo-igreja">Comunhão Cristã Abba FRG</span>
            </div>

            <div class="nav-secao">Visão geral</div>
            <?php echo navItem('/index.php', 'Dashboard', 'index', $paginaAtual, $iconDashboard); ?>

            <div class="nav-secao">Gestão</div>

            <?php if ($isAdmin): ?>
                <?php echo navItem('/pessoas.php', 'Pessoas', 'pessoas', $paginaAtual, $iconPessoas); ?>
            <?php endif; ?>

            <div class="nav-item-grupo <?php echo $gruposAberto ? 'aberto' : ''; ?>">
                <button class="nav-item nav-item-trigger <?php echo $gruposAberto ? 'ativo' : ''; ?>"
                    onclick="toggleSubmenu(this)" type="button">
                    <?php echo $iconGrupos; ?>
                    <span>Grupos Familiares</span>
                    <?php echo $iconChevron; ?>
                </button>
                <div class="nav-submenu">
                    <?php if ($isAdmin): ?>
                        <a href="/grupos_familiares.php"
                            class="nav-subitem <?php echo $paginaAtual === 'grupos_familiares' ? 'ativo' : ''; ?>">Cadastro de GFs</a>
                    <?php endif; ?>
                    <a href="/diagnostico_gf.php"
                        class="nav-subitem <?php echo $paginaAtual === 'diagnostico_gf' ? 'ativo' : ''; ?>">Diagnóstico de GFs</a>
                </div>
            </div>

            <?php echo navItem('/presencas.php', 'Reuniões e Presenças', 'presencas', $paginaAtual, $iconPresencas); ?>
            <?php
            // Agenda ativo em qualquer página de agenda
            $agendaAtivo = in_array($paginaAtual, $agendaPaginas, true) ? 'agenda' : $paginaAtual;
            echo navItem('/agenda.php', 'Agenda', 'agenda', $agendaAtivo, $iconAgenda);
            ?>
            <?php echo navItem('/cartas.php', 'Carta Semanal', 'cartas', $paginaAtual, $iconCarta, $totalCartasBadge); ?>

            <div class="nav-secao">Sistema</div>
            <?php echo navItem('/avisos.php', 'Notificações', 'avisos', $paginaAtual, $iconNotificacoes, $totalAvisosNav); ?>
            <?php if ($isAdmin): ?>
                <?php echo navItem('/auditoria.php', 'Auditoria', 'auditoria', $paginaAtual, $iconAuditoria); ?>
            <?php endif; ?>

            <div class="sidebar-footer">
                <a href="/meu_perfil.php" class="sidebar-usuario">
                    <div class="usuario-avatar"><?php echo htmlspecialchars($iniciais); ?></div>
                    <div>
                        <span class="usuario-nome"><?php echo htmlspecialchars($nomeUsuario); ?></span>
                        <span class="usuario-perfil"><?php echo htmlspecialchars($perfilUsuario); ?></span>
                    </div>
                </a>
                <a href="/logout.php" class="sidebar-sair">
                    <?php echo $iconSair; ?>
                    <span>Sair</span>
                </a>
            </div>
        </aside>
        <main class="jtro-main">
            <script>
                function toggleSubmenu(btn) {
                    const grupo = btn.closest('.nav-item-grupo');
                    if (grupo) grupo.classList.toggle('aberto');
                }
                document.addEventListener('DOMContentLoaded', function() {
                    const pa = <?php echo json_encode($paginaAtual); ?>;
                    const gp = <?php echo json_encode($gruposPaginas); ?>;
                    if (gp.includes(pa)) {
                        const g = document.querySelector('.nav-item-grupo');
                        if (g) g.classList.add('aberto');
                    }
                });
            </script>