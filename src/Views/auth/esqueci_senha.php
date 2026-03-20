<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Esqueci minha senha - JTRO</title>
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

        <h1 class="auth-titulo">Esqueci minha senha</h1>
        <p class="auth-subtitulo">Informe seu e-mail cadastrado e enviaremos um link para redefinir sua senha.</p>

        <?php if ($mensagem !== ''): ?>
            <div class="login-mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="login-erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="/esqueci_senha.php">
            <div class="campo">
                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="seu@email.com"
                    autocomplete="email">
                <small>Informe o e-mail cadastrado no sistema.</small>
            </div>

            <button type="submit" class="login-btn-entrar">Enviar link de redefinição</button>
        </form>

        <a href="/login.php" class="login-link-esqueci">← Voltar para o login</a>
    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>