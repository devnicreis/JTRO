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

    // Registro do Service Worker (PWA)
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function (error) {
                console.warn('[JTRO] Falha ao registrar Service Worker:', error);
            });
        });
    }
});
