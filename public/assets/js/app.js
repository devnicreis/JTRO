document.addEventListener('DOMContentLoaded', function () {
    const campoData = document.getElementById('data');
    const erroData = document.getElementById('erro-data');

    if (!campoData) return;

    const hoje = new Date();
    const hojeStr = hoje.toISOString().split('T')[0];

    const limitePassado = new Date();
    limitePassado.setDate(limitePassado.getDate() - 30);
    const minStr = limitePassado.toISOString().split('T')[0];

    campoData.min = minStr;
    campoData.max = hojeStr;

    campoData.addEventListener('change', function () {
        const valor = this.value;

        if (valor && (valor < minStr || valor > hojeStr)) {
            if (erroData) {
                erroData.style.display = 'block';
            }
            this.value = '';
        } else {
            if (erroData) {
                erroData.style.display = 'none';
            }
        }
    });
});