<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/pessoas.php">← Voltar para Pessoas</a>
</div>

<h1>Editar Pessoa</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/pessoas_editar.php">
    <input type="hidden" name="id" value="<?php echo $pessoa['id']; ?>">

    <div class="campo">
        <label for="nome">Nome</label>
        <input
            type="text"
            id="nome"
            name="nome"
            required
            pattern="^[A-Za-zÀ-ÿ\s]+$"
            title="Digite apenas letras e espaços."
            value="<?php echo htmlspecialchars($pessoa['nome']); ?>"
        >
        <small>Digite somente letras e espaços.</small>
    </div>

    <div class="campo">
        <label for="cpf">CPF</label>
        <input
            type="text"
            id="cpf"
            name="cpf"
            required
            inputmode="numeric"
            maxlength="11"
            pattern="\d{11}"
            title="Digite somente números, sem pontos e traços."
            value="<?php echo htmlspecialchars($pessoa['cpf']); ?>"
        >
        <small>Digite somente números, sem pontos e traços.</small>
    </div>

    <div class="campo">
        <label for="cargo">Perfil do sistema</label>
        <select id="cargo" name="cargo" required>
            <option value="membro" <?php echo $pessoa['cargo'] === 'membro' ? 'selected' : ''; ?>>Membro</option>
            <option value="admin" <?php echo $pessoa['cargo'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select>
    </div>

    <button type="submit">Salvar alterações</button>
</form>

<?php require __DIR__ . '/../layouts/footer.php'; ?>