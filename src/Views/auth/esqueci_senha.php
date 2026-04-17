<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Esqueci minha senha - JTRO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#185FA5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="JTRO">
    <?php echo Auth::csrfMetaTag(); ?>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/icons/pwa-192.png">
    <link rel="apple-touch-icon" href="/assets/icons/pwa-192.png">
    <link rel="stylesheet" href="/assets/css/app.css">
    <?php if ($turnstileEnabled): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
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

            <?php if ($turnstileEnabled): ?>
                <div class="login-turnstile">
                    <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstileSiteKey); ?>" data-size="flexible"></div>
                </div>
            <?php endif; ?>

            <button type="submit" class="login-btn-entrar">Enviar link de redefini&ccedil;&atilde;o</button>
        </form>

        <a href="/login.php" class="login-link-esqueci">Voltar para o login</a>
        <div class="auth-documentos">
            <a href="/termos_uso.php" target="_blank" rel="noopener noreferrer">Termos de Uso</a>
            <a href="/politica_privacidade.php" target="_blank" rel="noopener noreferrer">Pol&iacute;tica de Privacidade</a>
        </div>
    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
