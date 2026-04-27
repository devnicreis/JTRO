<?php require __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <h1>Carta Semanal</h1>
    <a href="/cartas.php" style="font-size:13px; color:var(--color-text-muted);">← Voltar para Cartas</a>
</div>

<?php
$avisosList = [];
if (!empty($carta['avisos'])) {
    $avisosList = json_decode($carta['avisos'], true) ?? [];
}
$meses = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
$ts = strtotime($carta['data_carta']);
$dataExtenso = $ts ? date('j', $ts) . ' de ' . $meses[date('n',$ts)-1] . ' de ' . date('Y', $ts) : '';
?>

<div class="carta-visualizar-wrap">

    <!-- Cabeçalho da carta -->
    <div class="carta-vis-header">
        <div class="carta-vis-imagem">
            <img src="/assets/icons/capa-carta.jpg"
                 alt="Carta Semanal"
                 onerror="this.parentElement.style.display='none'">
        </div>
        <div class="carta-vis-meta">
            <span class="carta-vis-local">Fazenda Rio Grande, <?php echo htmlspecialchars($dataExtenso); ?></span>
            <span class="carta-vis-graça">Graça e paz</span>
        </div>
    </div>

    <!-- Conteúdo principal -->
    <?php if (!empty($carta['conteudo'])): ?>
        <div class="carta-vis-conteudo ql-editor">
            <?php echo $carta['conteudo']; // HTML do Quill, já sanitizado na entrada ?>
        </div>
    <?php endif; ?>

    <!-- Mensagem de domingo -->
    <?php if (!empty($carta['pregacao_titulo'])): ?>
        <div class="carta-vis-secao">
            <div class="carta-vis-secao-titulo">Mensagem de Domingo</div>
            <p class="carta-vis-pregacao-titulo">
                <strong><?php echo htmlspecialchars($carta['pregacao_titulo']); ?></strong>
                <?php if (!empty($carta['pregacao_link'])): ?>
                    &nbsp;<a href="<?php echo htmlspecialchars($carta['pregacao_link']); ?>"
                              target="_blank" rel="noopener"
                              class="carta-link-externo">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 3H3a1 1 0 00-1 1v9a1 1 0 001 1h9a1 1 0 001-1v-4M10 2h4v4M14 2L8 8"/></svg>
                        Assistir
                    </a>
                <?php endif; ?>
            </p>
            <div class="carta-vis-pergunta">
                O que aprendeu na mensagem que ouviu? Alguma dúvida? Algum testemunho neste tema?
                Se sentiu desafiado em algo? Compartilhe com o grupo suas experiências com esta mensagem.
            </div>
        </div>
    <?php endif; ?>

    <!-- Avisos -->
    <?php if (!empty($avisosList)): ?>
        <div class="carta-vis-secao">
            <div class="carta-vis-secao-titulo">Avisos da Semana</div>
            <?php foreach ($avisosList as $av): ?>
                <div class="carta-vis-aviso">
                    <div class="carta-vis-aviso-nome">
                        <?php if (($av['tipo'] ?? 'texto') === 'evento'): ?>
                            <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" style="flex-shrink:0;margin-top:2px;"><rect x="2" y="3" width="12" height="11" rx="2"/><path d="M5 1v4M11 1v4M2 7h12"/></svg>
                        <?php endif; ?>
                        <strong><?php echo htmlspecialchars($av['nome']); ?></strong>
                        <?php if (!empty($av['data'])): ?>
                            <span class="carta-vis-aviso-data">
                                — <?php echo date('d/m/Y', strtotime($av['data'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($av['conteudo'])): ?>
                        <div class="carta-vis-aviso-body"><?php echo htmlspecialchars($av['conteudo']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Assinatura -->
    <div class="carta-vis-assinatura">
        Continuem abençoados
    </div>

    <!-- Ações -->
    <div style="margin-top:24px; display:flex; gap:10px; flex-wrap:wrap;">
        <?php if (Auth::isAdmin()): ?>
            <a class="botao-link botao-secundario" href="/carta_editar.php?id=<?php echo $carta['id']; ?>">Editar carta</a>
        <?php endif; ?>
        <button onclick="window.print()" class="botao-link botao-secundario">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:5px;"><path d="M4 6V2h8v4M4 12H2a1 1 0 01-1-1V7a1 1 0 011-1h12a1 1 0 011 1v4a1 1 0 01-1 1h-2M4 10h8v4H4v-4z"/></svg>
            Imprimir / Salvar PDF
        </button>
    </div>

</div>

<!-- Estilos do Quill para renderização -->
<link href="/assets/vendor/quill/quill.snow.css" rel="stylesheet">

<style>
@media print {
    .jtro-sidebar, .page-header a, button, .botao-link { display: none !important; }
    .jtro-main { margin-left: 0 !important; padding: 20px !important; }
    .carta-visualizar-wrap { max-width: 100% !important; }
}
</style>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
