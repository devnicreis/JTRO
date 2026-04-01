<?php

require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/LoginAttemptRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/RequestContext.php';
require_once __DIR__ . '/../src/Services/TurnstileService.php';
require_once __DIR__ . '/../src/Services/LoginSecurityAlertService.php';

Auth::start();

if (Auth::check()) {
    header('Location: /index.php');
    exit;
}

$repo = new PessoaRepository();
$attemptRepo = new LoginAttemptRepository();
$turnstile = new TurnstileService();
$alertService = new LoginSecurityAlertService();
$erro = '';
$mensagem = '';
$pageTitle = 'Login - JTRO';
$turnstileEnabled = $turnstile->isEnabled();
$turnstileSiteKey = $turnstile->getSiteKey();
$cpfPreenchido = preg_replace('/\D+/', '', trim($_POST['cpf'] ?? ''));
$ipAddressAtual = RequestContext::clientIp();
$janelaExibicao = date('Y-m-d H:i:s', time() - (15 * 60));

$enviarAlertaBloqueio = static function (string $cpf, ?string $ipAddress) use ($attemptRepo, $repo, $alertService): void {
    if ($cpf === '') {
        return;
    }

    $janelaAlerta = date('Y-m-d H:i:s', time() - 86400);
    $alertaRecente = $attemptRepo->houveAlertaPorCpfDesde($cpf, $janelaAlerta);

    if ($alertaRecente) {
        return;
    }

    $pessoaBloqueada = $repo->buscarPorCpfAtivo($cpf);
    if ($pessoaBloqueada === null || empty($pessoaBloqueada['email'])) {
        return;
    }

    try {
        $alertService->enviarAlertaBloqueio($pessoaBloqueada, $ipAddress);
        $attemptRepo->registrar($cpf, $ipAddress, 'alert_sent');
    } catch (Throwable $exception) {
        error_log('[JTRO] Falha ao enviar alerta de bloqueio de login: ' . $exception->getMessage());
    }
};

if (isset($_GET['senha_redefinida']) && $_GET['senha_redefinida'] === '1') {
    $mensagem = 'Senha redefinida com sucesso. Faca login com sua nova senha.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrf();

    $cpf = $cpfPreenchido;
    $senha = $_POST['senha'] ?? '';
    $ipAddress = $ipAddressAtual;
    $janelaTentativas = $janelaExibicao;

    $falhasCpf = $cpf !== '' ? $attemptRepo->contarFalhasPorCpfDesde($cpf, $janelaTentativas) : 0;
    $falhasIp = $ipAddress !== null ? $attemptRepo->contarFalhasPorIpDesde($ipAddress, $janelaTentativas) : 0;
    $bloqueadoPorCpf = $falhasCpf >= 4;
    $bloqueadoPorIp = $falhasIp >= 15;

    if ($cpf === '' || $senha === '') {
        $erro = 'Preencha CPF e senha.';
    } elseif ($bloqueadoPorCpf || $bloqueadoPorIp) {
        $attemptRepo->registrar($cpf !== '' ? $cpf : 'desconhecido', $ipAddress, 'blocked');
        if ($bloqueadoPorCpf) {
            $enviarAlertaBloqueio($cpf, $ipAddress);
        }

        $erro = 'Muitas tentativas de login. Aguarde 15 minutos e tente novamente.';
    } elseif ($turnstileEnabled) {
        try {
            $turnstileValidation = $turnstile->validateSubmission($_POST['cf-turnstile-response'] ?? null, $ipAddress);
            if (!$turnstileValidation['success']) {
                $erro = 'Confirme a verificacao de seguranca para continuar.';
            }
        } catch (Throwable $exception) {
            error_log('[JTRO] Falha ao validar Turnstile no login: ' . $exception->getMessage());
            $erro = 'Nao foi possivel validar a verificacao de seguranca agora. Tente novamente.';
        }
    }

    if ($erro === '') {
        $usuario = $repo->buscarPorCpfAtivo($cpf);

        if (!$usuario || empty($usuario['senha_hash']) || !password_verify($senha, $usuario['senha_hash'])) {
            $attemptRepo->registrar($cpf, $ipAddress, 'failed');
            $falhasCpfApos = $cpf !== '' ? ($falhasCpf + 1) : 0;
            $falhasIpApos = $ipAddress !== null ? ($falhasIp + 1) : 0;
            $bloqueadoAgoraPorCpf = $falhasCpfApos >= 4;
            $bloqueadoAgoraPorIp = $falhasIpApos >= 15;

            if ($bloqueadoAgoraPorCpf || $bloqueadoAgoraPorIp) {
                $attemptRepo->registrar($cpf !== '' ? $cpf : 'desconhecido', $ipAddress, 'blocked');
                if ($bloqueadoAgoraPorCpf) {
                    $enviarAlertaBloqueio($cpf, $ipAddress);
                }
                $erro = 'Muitas tentativas de login. Aguarde 15 minutos e tente novamente.';
            } else {
                $erro = 'CPF/Senha incorretos.';
            }
        } else {
            $attemptRepo->limparFalhasPorCpf($cpf);
            Auth::login($usuario);
            header('Location: /index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php echo Auth::csrfMetaTag(); ?>
    <link rel="stylesheet" href="/assets/css/app.css">
    <?php if ($turnstileEnabled): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <div class="login-painel-esq">
            <div class="login-logo-topo">
                <span class="login-logo-nome">Comunhao Crista Abba</span>
                <span class="login-logo-sub">Fazenda Rio Grande</span>
            </div>

            <img src="/assets/icons/logo-com-nome-abaixo.png"
                 class="login-logo-img"
                 alt="Logo JTRO"
                 onerror="this.style.display='none'">
        </div>

        <div class="login-painel-dir">
            <h1 class="login-form-titulo">Bem-vindo de volta</h1>
            <p class="login-form-subtitulo">Entre com seu CPF e senha para acessar o sistema.</p>

            <?php if ($mensagem !== ''): ?>
                <div class="login-mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>

            <?php if ($erro !== ''): ?>
                <div class="login-erro"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="/login.php">
                <div class="campo">
                    <label for="cpf">CPF</label>
                    <input
                        type="text"
                        id="cpf"
                        name="cpf"
                        required
                        inputmode="numeric"
                        maxlength="11"
                        placeholder="Somente numeros"
                        autocomplete="username"
                        value="<?php echo htmlspecialchars($cpfPreenchido); ?>">
                </div>

                <div class="campo">
                    <label for="senha">Senha</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        required
                        placeholder="********"
                        autocomplete="current-password">
                </div>

                <?php if ($turnstileEnabled): ?>
                    <div class="login-turnstile">
                        <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstileSiteKey); ?>" data-size="flexible"></div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="login-btn-entrar">Entrar</button>
            </form>

            <a href="/esqueci_senha.php" class="login-link-esqueci">Esqueci minha senha</a>
            <div class="auth-documentos">
                <a href="/termos_uso.php" target="_blank" rel="noopener noreferrer">Termos de Uso</a>
                <a href="/politica_privacidade.php" target="_blank" rel="noopener noreferrer">Pol&iacute;tica de Privacidade</a>
            </div>
        </div>

    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
