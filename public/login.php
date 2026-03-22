<?php

require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';

Auth::start();

if (Auth::check()) {
    header('Location: /index.php');
    exit;
}

$repo = new PessoaRepository();
$erro = '';
$mensagem = '';
$pageTitle = 'Login - JTRO';

if (isset($_GET['senha_redefinida']) && $_GET['senha_redefinida'] === '1') {
    $mensagem = 'Senha redefinida com sucesso. Faça login com sua nova senha.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf   = trim($_POST['cpf'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($cpf === '' || $senha === '') {
        $erro = 'Preencha CPF e senha.';
    } else {
        $usuario = $repo->buscarPorCpfAtivo($cpf);

        if (!$usuario || empty($usuario['senha_hash']) || !password_verify($senha, $usuario['senha_hash'])) {
            $erro = 'CPF ou senha inválidos.';
        } else {
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
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- Painel esquerdo -->
        <div class="login-painel-esq">
            <div class="login-logo-topo">
                <span class="login-logo-nome">Comunhão Cristã Abba</span>
                <span class="login-logo-sub">Fazenda Rio Grande</span>
            </div>

            <img src="/assets/icons/logo-com-nome-abaixo.png"
                 class="login-logo-img"
                 alt="Logo JTRO"
                 onerror="this.style.display='none'">
        </div>
        <!-- /painel esquerdo -->

        <!-- Painel direito -->
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
                        placeholder="Somente números"
                        autocomplete="username">
                </div>

                <div class="campo">
                    <label for="senha">Senha</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        required
                        placeholder="••••••••"
                        autocomplete="current-password">
                </div>

                <button type="submit" class="login-btn-entrar">Entrar</button>
            </form>

            <a href="/esqueci_senha.php" class="login-link-esqueci">Esqueci minha senha</a>
        </div>
        <!-- /painel direito -->

    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>