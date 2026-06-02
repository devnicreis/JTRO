<?php
// src/Views/relatorios/index.php  — v2
// Variáveis disponíveis: $gruposFamiliares (array), $erro (string|null)
?>
<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="page-content">

  <!-- ── Cabeçalho da página ── -->
  <div class="page-header-bar">
    <div>
      <h1 class="page-title">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 17H5a2 2 0 00-2 2v0a2 2 0 002 2h14a2 2 0 002-2v0a2 2 0 00-2-2h-4"/>
          <path d="M12 3v14M9 7l3-4 3 4"/>
        </svg>
        Central de Relatórios
      </h1>
      <p class="page-subtitle">Gere relatórios em PDF para análise pastoral e administrativa da igreja.</p>
    </div>
  </div>

  <!-- ── Mensagem de erro ── -->
  <?php if ($erro): ?>
  <div class="rel-alert rel-alert--danger">
    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <?= htmlspecialchars($erro) ?>
  </div>
  <?php endif; ?>

  <!-- ── Tabs ── -->
  <div class="rel-tabs" role="tablist">
    <button class="rel-tab rel-tab--active" onclick="switchRelTab('geral', this)" role="tab" aria-selected="true">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
      </svg>
      Gerais
    </button>
    <button class="rel-tab" onclick="switchRelTab('gf', this)" role="tab" aria-selected="false">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
      </svg>
      Específicos por GF
    </button>
  </div>

  <!-- ════════════════════════════ TAB: GERAIS ════════════════════════════ -->
  <div id="rel-tab-geral" class="rel-tab-panel rel-tab-panel--active">

    <div class="rel-section-label">Relatórios Gerais da Igreja</div>

    <div class="rel-grid">

      <!-- ── Card: Mapeamento de Assiduidade Global ── -->
      <div class="rel-card" data-tipo="mapeamento_assiduidade">
        <div class="rel-card__top">
          <div class="rel-card__icon rel-card__icon--blue">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path d="M9 11l3 3L22 4"/>
              <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
            </svg>
          </div>
          <div class="rel-card__meta">
            <div class="rel-card__name">Mapeamento de Assiduidade Global</div>
            <div class="rel-card__desc">
              Total de reuniões, taxa geral de presenças, tendência mês a mês e comparativo de faltas justificadas
              vs. injustificadas. Inclui top GFs e ranking completo de assiduidade por grupo.
            </div>
          </div>
          <div class="rel-card__toggle">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </div>
        </div>

        <div class="rel-card__params">
          <span class="rel-param">📅 Data Inicial</span>
          <span class="rel-param">📅 Data Final</span>
        </div>

        <form class="rel-form" method="POST" action="relatorios.php" novalidate>
          <input type="hidden" name="tipo" value="mapeamento_assiduidade">
          <div class="rel-form__fields">
            <div class="rel-form__row">
              <div class="rel-form__group">
                <label for="ma-di">Data Inicial</label>
                <input type="date" id="ma-di" name="data_inicial" required>
              </div>
              <div class="rel-form__group">
                <label for="ma-df">Data Final</label>
                <input type="date" id="ma-df" name="data_final" required>
              </div>
            </div>
            <div class="rel-form__actions">
              <button type="submit" class="btn-rel-gerar btn-rel-gerar--blue">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Gerar PDF
              </button>
            </div>
          </div>
        </form>
      </div><!-- /card mapeamento -->

      <!-- ── Card: Termômetro de Sobrecarga ── -->
      <div class="rel-card" data-tipo="termometro_sobrecarga">
        <div class="rel-card__top">
          <div class="rel-card__icon rel-card__icon--amber">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path d="M14 14.76V3.5a2.5 2.5 0 00-5 0v11.26a4.5 4.5 0 105 0z"/>
            </svg>
          </div>
          <div class="rel-card__meta">
            <div class="rel-card__name">Termômetro de Sobrecarga</div>
            <div class="rel-card__desc">
              Ranking de líderes por score de carga pastoral, considerando volume de membros,
              faltas injustificadas e tempo de liderança. Identifica quem precisa de apoio.
            </div>
          </div>
          <div class="rel-card__toggle">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </div>
        </div>

        <div class="rel-card__params">
          <span class="rel-param">📅 Data Inicial</span>
          <span class="rel-param">📅 Data Final</span>
        </div>

        <form class="rel-form" method="POST" action="relatorios.php" novalidate>
          <input type="hidden" name="tipo" value="termometro_sobrecarga">
          <div class="rel-form__fields">
            <div class="rel-form__row">
              <div class="rel-form__group">
                <label for="ts-di">Data Inicial</label>
                <input type="date" id="ts-di" name="data_inicial" required>
              </div>
              <div class="rel-form__group">
                <label for="ts-df">Data Final</label>
                <input type="date" id="ts-df" name="data_final" required>
              </div>
            </div>
            <div class="rel-form__actions">
              <button type="submit" class="btn-rel-gerar btn-rel-gerar--amber">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Gerar PDF
              </button>
            </div>
          </div>
        </form>
      </div><!-- /card termômetro -->

    </div><!-- /rel-grid GERAL -->
  </div><!-- /tab-geral -->


  <!-- ═══════════════════════════ TAB: POR GF ════════════════════════════ -->
  <div id="rel-tab-gf" class="rel-tab-panel">

    <div class="rel-section-label">Relatório Completo por Grupo Familiar</div>

    <div class="rel-grid rel-grid--single">

      <!-- ── Card único: Diagnóstico Completo do GF ── -->
      <div class="rel-card rel-card--gf" data-tipo="diagnostico_gf">
        <div class="rel-card__top">
          <div class="rel-card__icon rel-card__icon--teal">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </div>
          <div class="rel-card__meta">
            <div class="rel-card__name">Diagnóstico Completo do Grupo Familiar</div>
            <div class="rel-card__desc">
              Relatório unificado por GF com: saúde geral do grupo (membros ativos, taxa de presença, item Celeiro),
              reuniões realizadas no período com alerta de anomalias (local ou horário fora do padrão),
              mapa de frequência individual por reunião, membros com faltas consecutivas e
              ranking de pontualidade.
            </div>
          </div>
          <div class="rel-card__toggle">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </div>
        </div>

        <div class="rel-card__params">
          <span class="rel-param">📅 Data Inicial</span>
          <span class="rel-param">📅 Data Final</span>
          <span class="rel-param">🏠 Grupo Familiar</span>
        </div>

        <form class="rel-form" method="POST" action="relatorios.php" novalidate>
          <input type="hidden" name="tipo" value="diagnostico_gf">
          <div class="rel-form__fields rel-form__fields--gf">
            <div class="rel-form__row">
              <div class="rel-form__group">
                <label for="dg-di">Data Inicial</label>
                <input type="date" id="dg-di" name="data_inicial" required>
              </div>
              <div class="rel-form__group">
                <label for="dg-df">Data Final</label>
                <input type="date" id="dg-df" name="data_final" required>
              </div>
              <div class="rel-form__group rel-form__group--wide">
                <label for="dg-gf">Grupo Familiar</label>
                <select id="dg-gf" name="gf_id" required>
                  <option value="">Selecione um GF...</option>
                  <?php foreach ($gruposFamiliares as $gf): ?>
                  <option value="<?= $gf['id'] ?>">
                    <?= htmlspecialchars($gf['nome']) ?> – <?= htmlspecialchars($gf['lider_nome']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="rel-form__actions">
              <button type="submit" class="btn-rel-gerar btn-rel-gerar--teal">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Gerar PDF
              </button>
            </div>
          </div>
        </form>
      </div><!-- /card diagnostico_gf -->

    </div><!-- /rel-grid GF -->
  </div><!-- /tab-gf -->

</div><!-- /page-content -->


<!-- ══════════════════════════════════ ESTILOS ══════════════════════════════════ -->
<style>
/* ── Tabs ────────────────────────────────── */
.rel-tabs {
  display: flex;
  gap: 4px;
  background: var(--white);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 5px;
  width: fit-content;
  margin-bottom: 24px;
}
.rel-tab {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 8px 18px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  background: transparent;
  color: #64748b;           /* cinza médio — neutro */
  font-family: var(--font-body);
  transition: background .15s, color .15s;
}
.rel-tab:hover:not(.rel-tab--active) {
  background: #f1f5f9;
  color: #334155;
}
.rel-tab--active {
  background: #334155;      /* cinza grafite — escolha do usuário */
  color: #fff;
}

/* ── Tab panels ── */
.rel-tab-panel          { display: none; }
.rel-tab-panel--active  { display: block; }

/* ── Section label ── */
.rel-section-label {
  font-family: var(--font-title);
  font-size: 12px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: .9px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.rel-section-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border-color);
}

/* ── Grid ── */
.rel-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}
.rel-grid--single {
  grid-template-columns: 1fr;
  max-width: 680px;
}

/* ── Card ── */
.rel-card {
  background: var(--white);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 0;
  transition: box-shadow .2s, border-color .2s, transform .15s;
}
.rel-card:hover {
  box-shadow: 0 4px 18px rgba(0,0,0,.07);
  border-color: #cbd5e1;
  transform: translateY(-1px);
}
.rel-card.is-open {
  border-color: #185FA5;
  box-shadow: 0 4px 18px rgba(24,95,165,.1);
}

/* ── Card top (clicável) ── */
.rel-card__top {
  display: flex;
  align-items: flex-start;
  gap: 13px;
  cursor: pointer;
  user-select: none;
  padding-bottom: 0;
}
.rel-card__icon {
  width: 44px; height: 44px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  margin-top: 1px;
}
.rel-card__icon--blue   { background: var(--blue-light);   color: var(--primary-blue); }
.rel-card__icon--amber  { background: var(--amber-light);  color: var(--amber); }
.rel-card__icon--teal   { background: var(--teal-light);   color: var(--teal); }
.rel-card__icon--purple { background: var(--purple-light); color: var(--purple); }

.rel-card__meta  { flex: 1; }
.rel-card__name  {
  font-family: var(--font-title);
  font-size: 14.5px;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.35;
}
.rel-card__desc  {
  font-size: 12.5px;
  color: var(--text-muted);
  line-height: 1.55;
  margin-top: 4px;
}

/* Chevron toggle */
.rel-card__toggle {
  color: #94a3b8;
  flex-shrink: 0;
  margin-top: 4px;
  transition: transform .2s;
}
.rel-card.is-open .rel-card__toggle { transform: rotate(180deg); }

/* ── Param chips ── */
.rel-card__params {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 14px;
}
.rel-param {
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: 6px;
  font-size: 11.5px;
  color: #64748b;
  padding: 3px 9px;
  font-weight: 500;
}

/* ── Form ── */
.rel-form { display: none; margin-top: 14px; }
.rel-card.is-open .rel-form { display: block; }

.rel-form__fields {
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: 9px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Row de inputs lado a lado */
.rel-form__row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

/* Linha de ação: botão fica em linha separada, alinhado à direita */
.rel-form__actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 2px;
}

.rel-form__group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.rel-form__group--wide { flex: 1; min-width: 200px; }

.rel-form__group label {
  font-size: 10.5px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.rel-form__group input[type="date"],
.rel-form__group select {
  padding: 7px 11px;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  font-family: var(--font-body);
  font-size: 13px;
  background: var(--white);
  color: var(--text-primary);
  outline: none;
  transition: border-color .15s, box-shadow .15s;
  min-width: 130px;
}
.rel-form__group input[type="date"]:focus,
.rel-form__group select:focus {
  border-color: #185FA5;
  box-shadow: 0 0 0 3px rgba(24,95,165,.1);
}

/* ── Botão Gerar PDF ── */
.btn-rel-gerar {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 20px;
  border-radius: 8px;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: filter .15s, transform .1s;
  color: #fff;
  white-space: nowrap;
}
.btn-rel-gerar:hover  { filter: brightness(1.08); transform: translateY(-1px); }
.btn-rel-gerar:active { filter: brightness(.95);  transform: translateY(0); }
.btn-rel-gerar--blue   { background: #185FA5; }
.btn-rel-gerar--amber  { background: #854F0B; }
.btn-rel-gerar--teal   { background: #0F6E56; }
.btn-rel-gerar--purple { background: #534AB7; }

/* ── Alert ── */
.rel-alert {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 13px;
  margin-bottom: 18px;
  font-weight: 500;
}
.rel-alert--danger {
  background: var(--red-light);
  border: 1px solid #f0c4c0;
  color: var(--red);
}
</style>


<!-- ══════════════════════════════════ SCRIPTS ══════════════════════════════════ -->
<script>
/* Troca de tab */
function switchRelTab(id, btn) {
  document.querySelectorAll('.rel-tab-panel').forEach(p => p.classList.remove('rel-tab-panel--active'));
  document.querySelectorAll('.rel-tab').forEach(t => {
    t.classList.remove('rel-tab--active');
    t.setAttribute('aria-selected', 'false');
  });
  document.getElementById('rel-tab-' + id).classList.add('rel-tab-panel--active');
  btn.classList.add('rel-tab--active');
  btn.setAttribute('aria-selected', 'true');
}

/* Expand/collapse dos cards */
document.querySelectorAll('.rel-card__top').forEach(top => {
  top.addEventListener('click', function () {
    const card = this.closest('.rel-card');
    const isOpen = card.classList.contains('is-open');

    // Fechar todos os outros cards no mesmo painel
    const painel = card.closest('.rel-tab-panel');
    painel.querySelectorAll('.rel-card').forEach(c => c.classList.remove('is-open'));

    if (!isOpen) {
      card.classList.add('is-open');
      // Focar primeiro input disponível
      const primeiro = card.querySelector('input[type="date"], select');
      if (primeiro) setTimeout(() => primeiro.focus(), 80);
    }
  });
});

/* Validação inline de datas */
document.querySelectorAll('.rel-form').forEach(form => {
  form.addEventListener('submit', function (e) {
    const di = this.querySelector('[name="data_inicial"]');
    const df = this.querySelector('[name="data_final"]');
    if (di && df && di.value && df.value && df.value < di.value) {
      e.preventDefault();
      df.setCustomValidity('A data final deve ser maior ou igual à data inicial.');
      df.reportValidity();
    } else if (df) {
      df.setCustomValidity('');
    }
  });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
