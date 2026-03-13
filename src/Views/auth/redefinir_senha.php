<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/login.php">← Voltar para login</a>
</div>

<h1>Redefinir senha</h1>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<?php if ($erro === ''): ?>
    <form method="POST" action="/redefinir_senha.php">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

        <div class="campo">
            <label for="nova_senha">Nova senha</label>
            <input type="password" id="nova_senha" name="nova_senha" required minlength="8">
            <small>Mínimo de 8 caracteres, com letra maiúscula, minúscula, número e símbolo.</small>
        </div>

        <div class="campo">
            <label for="confirmar_senha">Confirmar nova senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="8">
        </div>

        <button type="submit">Redefinir senha</button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>