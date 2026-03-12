document.addEventListener('DOMContentLoaded', function () {
    const nomeInput = document.getElementById('nome');
    const cpfInput = document.getElementById('cpf');

    if (nomeInput) {
        nomeInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^A-Za-zÀ-ÿ\s]/g, '');
        });
    }

    if (cpfInput) {
        cpfInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 11);
        });
    }
});