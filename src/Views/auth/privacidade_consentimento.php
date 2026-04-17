<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card auth-card-wide">
        <div class="auth-logo">
            <span class="auth-logo-nome">JTRO</span>
            <span class="auth-logo-sub">The Relational Organizer</span>
        </div>

        <h1 class="auth-titulo">Privacidade e LGPD</h1>
        <p class="auth-subtitulo">Antes de continuar, <?php echo htmlspecialchars($nomeUsuario); ?>, precisamos registrar o seu aceite dos documentos atuais do JTRO.</p>

        <?php if ($erro !== ''): ?>
            <div class="login-erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <div class="privacy-panel">
            <p class="privacy-panel-texto">Leia os documentos abaixo. O aceite fica registrado com data, hora, IP e vers&atilde;o dos documentos.</p>

            <div class="privacy-links">
                <a href="/termos_uso.php" target="_blank" rel="noopener noreferrer" class="botao-link botao-secundario">Termos de Uso (vers&atilde;o <?php echo htmlspecialchars($termosVersao); ?>)</a>
                <a href="/politica_privacidade.php" target="_blank" rel="noopener noreferrer" class="botao-link botao-secundario">Pol&iacute;tica de Privacidade (vers&atilde;o <?php echo htmlspecialchars($politicaVersao); ?>)</a>
            </div>
        </div>

        <form class="login-form" method="POST" action="/privacidade_consentimento.php">
            <div class="privacy-checkbox">
                <input type="checkbox" id="aceito_privacidade" name="aceito_privacidade" value="1" required>
                <label for="aceito_privacidade">Li e concordo com os <a href="/termos_uso.php" target="_blank" rel="noopener noreferrer">Termos de Uso</a> e a <a href="/politica_privacidade.php" target="_blank" rel="noopener noreferrer">Pol&iacute;tica de Privacidade</a> do JTRO, e consinto com o tratamento dos meus dados pessoais conforme descrito.</label>
            </div>

            <button type="submit" class="login-btn-entrar">Continuar</button>
        </form>

        <a href="/logout.php" class="login-link-esqueci">Sair</a>
    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
