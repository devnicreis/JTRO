<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir senha - JTRO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-logo">
            <span class="auth-logo-nome">JTRO</span>
            <span class="auth-logo-sub">The Relational Organizer</span>
        </div>

        <h1 class="auth-titulo">Redefinir senha</h1>
        <p class="auth-subtitulo">Escolha uma senha segura para sua conta.</p>

        <?php if ($erro !== ''): ?>
            <div class="login-erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php if ($erro === ''): ?>
            <form class="login-form" method="POST" action="/redefinir_senha.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="campo">
                    <label for="nova_senha">Nova senha</label>
                    <input
                        type="password"
                        id="nova_senha"
                        name="nova_senha"
                        required
                        minlength="8"
                        placeholder="Mínimo 8 caracteres"
                        autocomplete="new-password">
                    <small>Use letras maiúsculas, minúsculas, números e símbolos.</small>
                </div>

                <div class="campo">
                    <label for="confirmar_senha">Confirmar nova senha</label>
                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        required
                        minlength="8"
                        placeholder="Repita a senha"
                        autocomplete="new-password">
                </div>

                <button type="submit" class="login-btn-entrar">Redefinir senha</button>
            </form>
        <?php endif; ?>

        <a href="/login.php" class="login-link-esqueci">← Voltar para o login</a>
    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>