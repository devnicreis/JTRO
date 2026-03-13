<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <?php if ($forcarTroca): ?>
        <a href="/logout.php">← Voltar para login</a>
    <?php else: ?>
        <a href="/index.php">← Voltar para início</a>
    <?php endif; ?>
</div>

<h1>Meu Perfil</h1>

<?php if ($forcarTroca): ?>
    <div class="erro">
        No primeiro acesso, você precisa definir uma nova senha antes de continuar.
    </div>
<?php endif; ?>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<div class="card-perfil">
    <h2>Dados da conta</h2>

    <div class="grid-perfil">
        <div class="campo">
            <label>Nome</label>
            <input type="text" value="<?php echo htmlspecialchars($pessoa['nome']); ?>" readonly>
        </div>

        <div class="campo">
            <label>CPF</label>
            <input type="text" value="<?php echo htmlspecialchars($pessoa['cpf']); ?>" readonly>
        </div>
    </div>

    <div class="campo">
        <label>Perfil do sistema</label>
        <input type="text" value="<?php echo htmlspecialchars($pessoa['cargo']); ?>" readonly>
    </div>
</div>

<div class="card-perfil">
    <h2>E-mail de recuperação</h2>

    <form method="POST" action="/meu_perfil.php<?php echo $forcarTroca ? '?forcar_troca=1' : ''; ?>">
        <input type="hidden" name="acao" value="atualizar_email">

        <div class="campo">
            <label for="email">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?php echo htmlspecialchars($pessoa['email'] ?? ''); ?>"
                required
            >
            <small>Esse e-mail será usado para recuperação de senha.</small>
        </div>

        <button type="submit">Atualizar e-mail</button>
    </form>
</div>

<div class="card-perfil">
    <h2>Segurança da conta</h2>

    <form method="POST" action="/meu_perfil.php<?php echo $forcarTroca ? '?forcar_troca=1' : ''; ?>">
        <input type="hidden" name="acao" value="alterar_senha">

        <?php if (!$forcarTroca): ?>
            <div class="campo">
                <label for="senha_atual">Senha atual</label>
                <input type="password" id="senha_atual" name="senha_atual" required>
            </div>
        <?php endif; ?>

        <div class="campo">
            <label for="nova_senha">Nova senha</label>
            <input type="password" id="nova_senha" name="nova_senha" required minlength="8">
            <small>Mínimo de 8 caracteres, com letra maiúscula, minúscula, número e símbolo.</small>
        </div>

        <div class="campo">
            <label for="confirmar_senha">Confirmar nova senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="8">
        </div>

        <button type="submit">Alterar senha</button>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>