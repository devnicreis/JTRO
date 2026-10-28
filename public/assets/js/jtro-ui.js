/* ==========================================================================
   JTRO UI — comportamento do shell e da Tela Inicial
   Destino no projeto: public/assets/js/jtro-ui.js (carregar DEPOIS de app.js)
   Sem dependências. Tudo é melhoria progressiva: sem JS a página continua
   navegável (links reais, conteúdo já renderizado no servidor).
   ========================================================================== */
(function () {
    'use strict';

    var reduzMovimento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var ehPrototipo = document.documentElement.hasAttribute('data-ui-prototipo');
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    /* ---------- 1. Gaveta (menu completo no celular) ---------- */
    function iniciarGaveta() {
        var sidebar = document.getElementById('uiSidebar');
        if (!sidebar) return;
        var ultimoFoco = null;

        function abrir() {
            ultimoFoco = document.activeElement;
            document.body.classList.add('ui-drawer-aberto');
            document.querySelectorAll('[data-ui-gaveta="abrir"]').forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
            var primeiro = sidebar.querySelector('a, button, summary, input');
            if (primeiro) setTimeout(function () { primeiro.focus(); }, 50);
        }
        function fechar() {
            if (!document.body.classList.contains('ui-drawer-aberto')) return;
            document.body.classList.remove('ui-drawer-aberto');
            document.querySelectorAll('[data-ui-gaveta="abrir"]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
            if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
        }
        document.addEventListener('click', function (e) {
            var alvo = e.target.closest('[data-ui-gaveta]');
            if (!alvo) return;
            e.preventDefault();
            if (alvo.getAttribute('data-ui-gaveta') === 'abrir') abrir(); else fechar();
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') fechar(); });
        window.matchMedia('(min-width: 1024px)').addEventListener('change', function (m) { if (m.matches) fechar(); });
    }

    /* ---------- 2. Abas acessíveis ---------- */
    function iniciarAbas() {
        document.querySelectorAll('[data-ui-abas]').forEach(function (lista) {
            var abas = Array.prototype.slice.call(lista.querySelectorAll('[role="tab"]'));
            function selecionar(aba, focar) {
                abas.forEach(function (a) {
                    var ativa = a === aba;
                    a.setAttribute('aria-selected', ativa ? 'true' : 'false');
                    a.tabIndex = ativa ? 0 : -1;
                    var painel = document.getElementById(a.getAttribute('aria-controls'));
                    if (painel) painel.hidden = !ativa;
                });
                if (focar) aba.focus();
            }
            abas.forEach(function (aba, i) {
                aba.addEventListener('click', function () { selecionar(aba, false); });
                aba.addEventListener('keydown', function (e) {
                    var prox = null;
                    if (e.key === 'ArrowRight') prox = abas[(i + 1) % abas.length];
                    if (e.key === 'ArrowLeft') prox = abas[(i - 1 + abas.length) % abas.length];
                    if (e.key === 'Home') prox = abas[0];
                    if (e.key === 'End') prox = abas[abas.length - 1];
                    if (prox) { e.preventDefault(); selecionar(prox, true); }
                });
            });
        });
    }

    /* ---------- 3. Números que contam até o valor (uma vez) ---------- */
    function iniciarContagem() {
        var alvos = document.querySelectorAll('[data-ui-contar]');
        if (reduzMovimento || !alvos.length || !window.requestAnimationFrame) return;
        alvos.forEach(function (el) {
            var final = parseFloat(el.getAttribute('data-ui-contar'));
            if (!isFinite(final) || final <= 0) return;
            var sufixo = el.getAttribute('data-ui-sufixo') || '';
            var casas = parseInt(el.getAttribute('data-ui-decimais') || '0', 10);
            var formato = { minimumFractionDigits: casas, maximumFractionDigits: casas };
            var inicio = null;
            var duracao = 1100;
            function passo(t) {
                if (inicio === null) inicio = t;
                var p = Math.min(1, (t - inicio) / duracao);
                var e = 1 - Math.pow(1 - p, 3);
                el.textContent = (final * e).toLocaleString('pt-BR', formato) + sufixo;
                if (p < 1) requestAnimationFrame(passo);
            }
            el.textContent = (0).toLocaleString('pt-BR', formato) + sufixo;
            requestAnimationFrame(passo);
        });
    }

    /* ---------- 4. Mostrar/ocultar (ex.: lista de GFs pendentes) ---------- */
    function iniciarAlternar() {
        document.addEventListener('click', function (e) {
            var botao = e.target.closest('[data-ui-alternar]');
            if (!botao) return;
            var alvo = document.getElementById(botao.getAttribute('data-ui-alternar'));
            if (!alvo) return;
            e.preventDefault();
            alvo.hidden = !alvo.hidden;
            botao.setAttribute('aria-expanded', alvo.hidden ? 'false' : 'true');
            var rotulo = botao.querySelector('[data-ui-rotulo]');
            if (rotulo) {
                rotulo.textContent = alvo.hidden
                    ? rotulo.getAttribute('data-ui-rotulo-fechado')
                    : rotulo.getAttribute('data-ui-rotulo-aberto');
            }
            if (!alvo.hidden) alvo.scrollIntoView({ behavior: reduzMovimento ? 'auto' : 'smooth', block: 'nearest' });
        });
    }

    /* ---------- 5. Marcar alerta como lido (usa public/avisos_json.php) ---------- */
    function decrementar(chaveContador) {
        document.querySelectorAll('[data-ui-contador="' + chaveContador + '"]').forEach(function (el) {
            var n = Math.max(0, (parseInt(el.textContent, 10) || 0) - 1);
            el.textContent = String(n);
            if (n === 0 && el.hasAttribute('data-ui-ocultar-zero')) el.hidden = true;
        });
    }

    function iniciarMarcarLido() {
        document.addEventListener('click', function (e) {
            var botao = e.target.closest('[data-ui-marcar-lido]');
            if (!botao || botao.disabled) return;
            e.preventDefault();
            var chave = botao.getAttribute('data-ui-marcar-lido');
            var item = botao.closest('li');
            var painel = botao.closest('.ui-painel');
            botao.disabled = true;

            var envio = ehPrototipo
                ? Promise.resolve({ ok: true })
                : fetch('/avisos_json.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ acao: 'marcar_lido', chave: chave })
                });

            envio.then(function (r) {
                if (!r.ok) throw new Error('falha');
                var linha = item ? item.querySelector('.ui-linha') : null;
                if (linha) linha.classList.add('ui-linha--saindo');
                setTimeout(function () {
                    if (item) item.remove();
                    if (painel) {
                        decrementar(painel.id);
                        var restantes = painel.querySelectorAll('.ui-lista > li').length;
                        var vazio = painel.querySelector('[data-ui-vazio]');
                        if (restantes === 0 && vazio) vazio.hidden = false;
                    }
                    decrementar('avisos');
                    document.dispatchEvent(new CustomEvent('ui-aviso-lido', { detail: { chave: chave } }));
                }, reduzMovimento ? 0 : 280);
            }).catch(function () {
                botao.disabled = false;
                botao.setAttribute('aria-label', 'Não foi possível marcar como lido. Tente novamente.');
            });
        });
    }

    /* ---------- 6. Tema (somente quando houver o botão [data-ui-tema]) ---------- */
    function iniciarTema() {
        var html = document.documentElement;
        try {
            var salvo = localStorage.getItem('jtro-tema');
            if (salvo === 'dark') html.setAttribute('data-theme', 'dark');
        } catch (err) { /* armazenamento indisponível: segue no tema claro */ }
        document.addEventListener('click', function (e) {
            if (!e.target.closest('[data-ui-tema]')) return;
            var escuro = html.getAttribute('data-theme') !== 'dark';
            if (escuro) html.setAttribute('data-theme', 'dark'); else html.removeAttribute('data-theme');
            try { localStorage.setItem('jtro-tema', escuro ? 'dark' : 'light'); } catch (err) { /* ignora */ }
        });
    }

    function iniciar() {
        iniciarGaveta();
        iniciarAbas();
        iniciarContagem();
        iniciarAlternar();
        iniciarMarcarLido();
        if (document.querySelector('[data-ui-tema]')) iniciarTema();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();
})();
