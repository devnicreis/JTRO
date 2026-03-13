<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/login.php">← Voltar para login</a>
</div>

<h1>Esqueci minha senha</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/esqueci_senha.php">
    <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required>
        <small>Informe o e-mail cadastrado no sistema.</small>
    </div>

    <button type="submit">Enviar link de redefinição</button>
</form>

<?php require __DIR__ . '/../layouts/footer.php'; ?>