<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <h1>Central de Relatorios</h1>
    <p class="page-header-subtitulo">Gere PDFs analiticos para apoio pastoral e administrativo.</p>
</div>

<?php if (!empty($erro)): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<div class="relatorios-tabs" role="tablist" aria-label="Tipos de relatorios">
    <button type="button" class="relatorios-tab ativo" data-tab-target="gerais" role="tab" aria-selected="true">
        Gerais
    </button>
    <button type="button" class="relatorios-tab" data-tab-target="gf" role="tab" aria-selected="false">
        Especificos por GF
    </button>
</div>

<section id="relatorios-tab-gerais" class="relatorios-tab-panel ativo" role="tabpanel">
    <div class="relatorios-grid">
        <article class="relatorio-card" data-relatorio-card>
            <button type="button" class="relatorio-card-topo" data-relatorio-toggle>
                <span class="relatorio-icone relatorio-icone-azul">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M2 13h12M4 11V6M8 11V3M12 11V8"></path>
                    </svg>
                </span>
                <span class="relatorio-conteudo">
                    <span class="relatorio-titulo">Mapeamento de Assiduidade</span>
                    <span class="relatorio-descricao">KPIs gerais, evolucao mensal e comparativo por Grupo Familiar.</span>
                </span>
            </button>
            <div class="relatorio-chips">
                <span class="badge badge-blue">Data inicial</span>
                <span class="badge badge-blue">Data final</span>
            </div>
            <form method="POST" action="/relatorios.php" class="relatorio-formulario" data-relatorio-formulario>
                <?php echo Auth::csrfField(); ?>
                <input type="hidden" name="tipo" value="mapeamento_assiduidade">
                <div class="relatorio-form-grid">
                    <div class="campo">
                        <label for="map_data_inicial">Data Inicial</label>
                        <input id="map_data_inicial" type="date" name="data_inicial" required>
                    </div>
                    <div class="campo">
                        <label for="map_data_final">Data Final</label>
                        <input id="map_data_final" type="date" name="data_final" required>
                    </div>
                    <button type="submit" class="relatorio-btn relatorio-btn-azul">Gerar PDF</button>
                </div>
            </form>
        </article>

        <article class="relatorio-card" data-relatorio-card>
            <button type="button" class="relatorio-card-topo" data-relatorio-toggle>
                <span class="relatorio-icone relatorio-icone-amber">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M8 2v6M8 8a3 3 0 103 3"></path>
                    </svg>
                </span>
                <span class="relatorio-conteudo">
                    <span class="relatorio-titulo">Termometro de Sobrecarga</span>
                    <span class="relatorio-descricao">Score de sobrecarga por lider com nivel baixo, medio ou alto.</span>
                </span>
            </button>
            <div class="relatorio-chips">
                <span class="badge badge-amber">Data inicial</span>
                <span class="badge badge-amber">Data final</span>
            </div>
            <form method="POST" action="/relatorios.php" class="relatorio-formulario" data-relatorio-formulario>
                <?php echo Auth::csrfField(); ?>
                <input type="hidden" name="tipo" value="termometro_sobrecarga">
                <div class="relatorio-form-grid">
                    <div class="campo">
                        <label for="ter_data_inicial">Data Inicial</label>
                        <input id="ter_data_inicial" type="date" name="data_inicial" required>
                    </div>
                    <div class="campo">
                        <label for="ter_data_final">Data Final</label>
                        <input id="ter_data_final" type="date" name="data_final" required>
                    </div>
                    <button type="submit" class="relatorio-btn relatorio-btn-amber">Gerar PDF</button>
                </div>
            </form>
        </article>

        <article class="relatorio-card" data-relatorio-card>
            <button type="button" class="relatorio-card-topo" data-relatorio-toggle>
                <span class="relatorio-icone relatorio-icone-verde">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="5" cy="5" r="2.5"></circle>
                        <path d="M1.8 13.5a3.5 3.5 0 016.4 0M11 8.5l1.3 1.3 2.4-2.4"></path>
                    </svg>
                </span>
                <span class="relatorio-conteudo">
                    <span class="relatorio-titulo">Engajamento de Visitantes</span>
                    <span class="relatorio-descricao">Retorno e conversao de visitantes identificados nas reunioes.</span>
                </span>
            </button>
            <div class="relatorio-chips">
                <span class="badge badge-green">Data inicial</span>
                <span class="badge badge-green">Data final</span>
            </div>
            <form method="POST" action="/relatorios.php" class="relatorio-formulario" data-relatorio-formulario>
                <?php echo Auth::csrfField(); ?>
                <input type="hidden" name="tipo" value="engajamento_visitantes">
                <div class="relatorio-form-grid">
                    <div class="campo">
                        <label for="eng_data_inicial">Data Inicial</label>
                        <input id="eng_data_inicial" type="date" name="data_inicial" required>
                    </div>
                    <div class="campo">
                        <label for="eng_data_final">Data Final</label>
                        <input id="eng_data_final" type="date" name="data_final" required>
                    </div>
                    <button type="submit" class="relatorio-btn relatorio-btn-verde">Gerar PDF</button>
                </div>
            </form>
        </article>
    </div>
</section>

<section id="relatorios-tab-gf" class="relatorios-tab-panel" role="tabpanel">
    <div class="relatorios-grid">
        <article class="relatorio-card" data-relatorio-card>
            <button type="button" class="relatorio-card-topo" data-relatorio-toggle>
                <span class="relatorio-icone relatorio-icone-vermelho">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="8" cy="8" r="6"></circle>
                        <path d="M8 4.6v3.2M8 10.8h.01"></path>
                    </svg>
                </span>
                <span class="relatorio-conteudo">
                    <span class="relatorio-titulo">Raio-X de Vulnerabilidade</span>
                    <span class="relatorio-descricao">Membros com ausencias recorrentes e nivel de alerta por taxa.</span>
                </span>
            </button>
            <div class="relatorio-chips">
                <span class="badge badge-red">Data inicial</span>
                <span class="badge badge-red">Data final</span>
                <span class="badge badge-red">Grupo Familiar</span>
            </div>
            <form method="POST" action="/relatorios.php" class="relatorio-formulario" data-relatorio-formulario>
                <?php echo Auth::csrfField(); ?>
                <input type="hidden" name="tipo" value="raio_x_vulnerabilidade">
                <div class="relatorio-form-grid relatorio-form-grid-gf">
                    <div class="campo">
                        <label for="vul_data_inicial">Data Inicial</label>
                        <input id="vul_data_inicial" type="date" name="data_inicial" required>
                    </div>
                    <div class="campo">
                        <label for="vul_data_final">Data Final</label>
                        <input id="vul_data_final" type="date" name="data_final" required>
                    </div>
                    <div class="campo relatorio-campo-amplo">
                        <label for="vul_gf_id">Grupo Familiar</label>
                        <select id="vul_gf_id" name="gf_id" required>
                            <option value="">Selecione um GF</option>
                            <?php foreach ($gruposFamiliares as $gf): ?>
                                <option value="<?php echo (int) $gf['id']; ?>">
                                    <?php echo htmlspecialchars($gf['nome']); ?> - <?php echo htmlspecialchars($gf['lider_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="relatorio-btn relatorio-btn-vermelho">Gerar PDF</button>
                </div>
            </form>
        </article>

        <article class="relatorio-card" data-relatorio-card>
            <button type="button" class="relatorio-card-topo" data-relatorio-toggle>
                <span class="relatorio-icone relatorio-icone-teal">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M2.5 3.5h11v8h-11z"></path>
                        <path d="M5 6h6M5 8h6M5 10h4"></path>
                    </svg>
                </span>
                <span class="relatorio-conteudo">
                    <span class="relatorio-titulo">Historico de Oracao</span>
                    <span class="relatorio-descricao">Linha do tempo de pedidos de oracao registrados por membro.</span>
                </span>
            </button>
            <div class="relatorio-chips">
                <span class="badge badge-teal">Data inicial</span>
                <span class="badge badge-teal">Data final</span>
                <span class="badge badge-teal">Grupo Familiar</span>
            </div>
            <form method="POST" action="/relatorios.php" class="relatorio-formulario" data-relatorio-formulario>
                <?php echo Auth::csrfField(); ?>
                <input type="hidden" name="tipo" value="historico_oracao">
                <div class="relatorio-form-grid relatorio-form-grid-gf">
                    <div class="campo">
                        <label for="ora_data_inicial">Data Inicial</label>
                        <input id="ora_data_inicial" type="date" name="data_inicial" required>
                    </div>
                    <div class="campo">
                        <label for="ora_data_final">Data Final</label>
                        <input id="ora_data_final" type="date" name="data_final" required>
                    </div>
                    <div class="campo relatorio-campo-amplo">
                        <label for="ora_gf_id">Grupo Familiar</label>
                        <select id="ora_gf_id" name="gf_id" required>
                            <option value="">Selecione um GF</option>
                            <?php foreach ($gruposFamiliares as $gf): ?>
                                <option value="<?php echo (int) $gf['id']; ?>">
                                    <?php echo htmlspecialchars($gf['nome']); ?> - <?php echo htmlspecialchars($gf['lider_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="relatorio-btn relatorio-btn-teal">Gerar PDF</button>
                </div>
            </form>
        </article>

        <article class="relatorio-card" data-relatorio-card>
            <button type="button" class="relatorio-card-topo" data-relatorio-toggle>
                <span class="relatorio-icone relatorio-icone-roxo">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="8" cy="8" r="6"></circle>
                        <path d="M8 5v3l2 1.4"></path>
                    </svg>
                </span>
                <span class="relatorio-conteudo">
                    <span class="relatorio-titulo">Pontualidade e Frequencia</span>
                    <span class="relatorio-descricao">Taxa de presenca e pontualidade individual por GF.</span>
                </span>
            </button>
            <div class="relatorio-chips">
                <span class="badge badge-purple">Data inicial</span>
                <span class="badge badge-purple">Data final</span>
                <span class="badge badge-purple">Grupo Familiar</span>
            </div>
            <form method="POST" action="/relatorios.php" class="relatorio-formulario" data-relatorio-formulario>
                <?php echo Auth::csrfField(); ?>
                <input type="hidden" name="tipo" value="pontualidade_frequencia">
                <div class="relatorio-form-grid relatorio-form-grid-gf">
                    <div class="campo">
                        <label for="pon_data_inicial">Data Inicial</label>
                        <input id="pon_data_inicial" type="date" name="data_inicial" required>
                    </div>
                    <div class="campo">
                        <label for="pon_data_final">Data Final</label>
                        <input id="pon_data_final" type="date" name="data_final" required>
                    </div>
                    <div class="campo relatorio-campo-amplo">
                        <label for="pon_gf_id">Grupo Familiar</label>
                        <select id="pon_gf_id" name="gf_id" required>
                            <option value="">Selecione um GF</option>
                            <?php foreach ($gruposFamiliares as $gf): ?>
                                <option value="<?php echo (int) $gf['id']; ?>">
                                    <?php echo htmlspecialchars($gf['nome']); ?> - <?php echo htmlspecialchars($gf['lider_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="relatorio-btn relatorio-btn-roxo">Gerar PDF</button>
                </div>
            </form>
        </article>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = Array.from(document.querySelectorAll('.relatorios-tab'));
    const paineis = {
        gerais: document.getElementById('relatorios-tab-gerais'),
        gf: document.getElementById('relatorios-tab-gf')
    };

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const alvo = tab.getAttribute('data-tab-target');
            tabs.forEach(function (botao) {
                botao.classList.remove('ativo');
                botao.setAttribute('aria-selected', 'false');
            });
            Object.values(paineis).forEach(function (painel) {
                painel.classList.remove('ativo');
            });
            tab.classList.add('ativo');
            tab.setAttribute('aria-selected', 'true');
            if (alvo && paineis[alvo]) {
                paineis[alvo].classList.add('ativo');
            }
        });
    });

    const cards = Array.from(document.querySelectorAll('[data-relatorio-card]'));
    cards.forEach(function (card) {
        const toggle = card.querySelector('[data-relatorio-toggle]');
        const formulario = card.querySelector('[data-relatorio-formulario]');
        if (!toggle || !formulario) {
            return;
        }

        toggle.addEventListener('click', function () {
            const aberto = card.classList.contains('aberto');
            cards.forEach(function (outro) {
                outro.classList.remove('aberto');
            });
            if (!aberto) {
                card.classList.add('aberto');
                const primeiroCampo = formulario.querySelector('input[type="date"], select');
                if (primeiroCampo) {
                    setTimeout(function () { primeiroCampo.focus(); }, 80);
                }
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
