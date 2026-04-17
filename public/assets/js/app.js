/* ============================================================
   JTRO — app.js
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function (form) {
        if (!csrfToken || form.querySelector('input[name="_csrf"]')) {
            return;
        }

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = '_csrf';
        hiddenInput.value = csrfToken;
        form.prepend(hiddenInput);
    });

    // ── Validação de campo de data em formulários ──────────────
    // Só aplica restrição de data passada em páginas que NÃO sejam da agenda
    const paginaAtual = window.location.pathname;
    const ehPaginaAgenda = paginaAtual.includes('agenda');

    const campoData = document.getElementById('data');
    const erroData  = document.getElementById('erro-data');

    if (campoData && !ehPaginaAgenda) {
        const hoje = new Date();
        const hojeStr = hoje.toISOString().split('T')[0];
        const limitePassado = new Date();
        limitePassado.setDate(limitePassado.getDate() - 30);
        const minStr = limitePassado.toISOString().split('T')[0];

        campoData.min = minStr;
        campoData.max = hojeStr;

        campoData.addEventListener('change', function () {
            if (this.value && (this.value < minStr || this.value > hojeStr)) {
                if (erroData) erroData.style.display = 'block';
                this.value = '';
            } else {
                if (erroData) erroData.style.display = 'none';
            }
        });
    }

    // ── Sino de notificações ───────────────────────────────────
    // Menu lateral mobile (off-canvas)
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const mobileNavOverlay = document.getElementById('mobileNavOverlay');
    const mobileSidebar = document.getElementById('jtroSidebar');

    if (mobileNavToggle && mobileNavOverlay && mobileSidebar) {
        mobileNavOverlay.hidden = true;
        const closeMobileNav = function () {
            document.body.classList.remove('mobile-nav-open');
            mobileNavToggle.setAttribute('aria-expanded', 'false');
            mobileNavOverlay.hidden = true;
        };

        const openMobileNav = function () {
            document.body.classList.add('mobile-nav-open');
            mobileNavToggle.setAttribute('aria-expanded', 'true');
            mobileNavOverlay.hidden = false;
        };

        mobileNavToggle.addEventListener('click', function () {
            if (document.body.classList.contains('mobile-nav-open')) {
                closeMobileNav();
                return;
            }

            openMobileNav();
        });

        mobileNavOverlay.addEventListener('click', closeMobileNav);

        mobileSidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 900) {
                    closeMobileNav();
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMobileNav();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) {
                closeMobileNav();
            }
        });
    }

    // Registro do Service Worker (PWA)
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function (error) {
                console.warn('[JTRO] Falha ao registrar Service Worker:', error);
            });
        });
    }

    const notifWrap       = document.getElementById('notifWrap');
    const notifBtn        = document.getElementById('notifBtn');
    const notifDropdown   = document.getElementById('notifDropdown');
    const notifList       = document.getElementById('notifList');
    const notifBadge      = document.getElementById('notifBadge');
    const notifMarcarTodos = document.getElementById('notifMarcarTodos');
    const notifCountEl    = document.getElementById('notifCountNaoLidos');

    if (!notifBtn || !notifDropdown) return;

    let avisosCache  = [];
    let abaAtiva     = 'nao-lidos';
    let dropdownOpen = false;

    // Abrir/fechar dropdown
    notifBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdownOpen = !dropdownOpen;
        notifDropdown.classList.toggle('aberto', dropdownOpen);
        if (dropdownOpen && avisosCache.length === 0) carregarAvisos();
    });

    // Fechar ao clicar fora
    document.addEventListener('click', function (e) {
        if (notifWrap && !notifWrap.contains(e.target)) {
            dropdownOpen = false;
            notifDropdown.classList.remove('aberto');
        }
    });

    // Abas não lidos / lidos
    notifDropdown.querySelectorAll('.notif-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            notifDropdown.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('ativo'));
            this.classList.add('ativo');
            abaAtiva = this.dataset.tab;
            renderAvisos();
        });
    });

    // Marcar todos como lidos
    if (notifMarcarTodos) {
        notifMarcarTodos.addEventListener('click', function () {
            const naoLidos = avisosCache.filter(a => !a.lido);
            if (naoLidos.length === 0) return;

            fetch('/avisos_json.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ acao: 'marcar_todos_lidos', chaves: naoLidos.map(a => a.chave), chave: '' })
            }).then(function () {
                avisosCache.forEach(a => a.lido = true);
                atualizarBadge();
                renderAvisos();
            });
        });
    }

    function carregarAvisos() {
        if (notifList) notifList.innerHTML = '<div class="notif-carregando">Carregando avisos...</div>';

        fetch('/avisos_json.php')
            .then(r => r.json())
            .then(function (data) {
                avisosCache = data.avisos || [];
                atualizarBadge();
                renderAvisos();
            })
            .catch(function () {
                if (notifList) notifList.innerHTML = '<div class="notif-carregando">Erro ao carregar avisos.</div>';
            });
    }

    // Expõe globalmente para a página de avisos sincronizar o sino
    window.jtroRecarregarAvisos = carregarAvisos;
    carregarAvisos();

    function atualizarBadge() {
        const count = avisosCache.filter(a => !a.lido).length;

        if (notifBadge) {
            notifBadge.textContent = count > 0 ? count : '';
            notifBadge.classList.toggle('notif-badge-oculto', count === 0);
        }

        if (notifCountEl) {
            notifCountEl.textContent = count > 0 ? '(' + count + ')' : '';
        }

        const sidebarBadge = document.querySelector('.nav-item[href="/avisos.php"] .nav-badge');
        if (sidebarBadge) {
            if (count > 0) {
                sidebarBadge.textContent = count;
                sidebarBadge.style.display = '';
            } else {
                sidebarBadge.style.display = 'none';
            }
        }

        document.dispatchEvent(new CustomEvent('notif-badge-atualizado', { detail: { count } }));
    }

    function tempoRelativo(ts) {
        if (!ts) return '';
        const agora = new Date();
        const data  = new Date(ts * 1000);
        const diffDias = Math.floor((agora - data) / 86400000);
        if (diffDias === 0) return 'Hoje';
        if (diffDias === 1) return 'Ontem';
        const meses = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
        return data.getDate() + ' ' + meses[data.getMonth()];
    }

    function renderAvisos() {
        if (!notifList) return;

        const filtrados = avisosCache.filter(function (a) {
            return abaAtiva === 'nao-lidos' ? !a.lido : a.lido;
        });

        if (filtrados.length === 0) {
            notifList.innerHTML = '<div class="notif-vazio">Nenhum aviso ' + (abaAtiva === 'nao-lidos' ? 'não lido' : 'lido') + '.</div>';
            return;
        }

        notifList.innerHTML = filtrados.map(function (aviso) {
            const tempo   = aviso.timestamp ? '<span class="notif-tempo">' + esc(tempoRelativo(aviso.timestamp)) + '</span>' : '';
            const detalhe = aviso.detalhe   ? '<div class="notif-detalhe">' + esc(aviso.detalhe) + '</div>' : '';
            const motivo  = aviso.motivo    ? '<div class="notif-motivo">'  + esc(aviso.motivo)  + '</div>' : '';
            const linkCta = aviso.link
                ? '<a class="notif-link-btn" href="' + esc(aviso.link) + '">' + esc(aviso.cta_label || 'ABRIR DETALHES') + '</a>'
                : '';

            return '<div class="notif-item" data-chave="' + esc(aviso.chave) + '">' +
                '<div class="notif-dot notif-dot-' + esc(aviso.tipo) + '"></div>' +
                '<div class="notif-item-corpo">' +
                '<div class="notif-texto">' + esc(aviso.texto) + '</div>' +
                detalhe + motivo + tempo + linkCta +
                '<button class="notif-acao-btn" data-chave="' + esc(aviso.chave) + '" data-lido="' + aviso.lido + '" type="button">' +
                (aviso.lido ? 'Marcar como não lido' : 'Marcar como lido') +
                '</button>' +
                '</div></div>';
        }).join('');

        notifList.querySelectorAll('.notif-acao-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const chave = this.dataset.chave;
                const lido  = this.dataset.lido === 'true';
                const acao  = lido ? 'marcar_nao_lido' : 'marcar_lido';

                fetch('/avisos_json.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ acao: acao, chave: chave })
                }).then(function () {
                    const aviso = avisosCache.find(a => a.chave === chave);
                    if (aviso) aviso.lido = !lido;
                    atualizarBadge();
                    renderAvisos();
                });
            });
        });
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

});

