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
    $cpf = trim($_POST['cpf'] ?? '');
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

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body>

    <h1>JTRO: seu organizador relacional</h1>
    <h2>Login</h2>

    <?php if ($erro !== ''): ?>
        <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <form method="POST" action="/login.php">
        <div class="campo">
            <label for="cpf">CPF</label>
            <input
                type="text"
                id="cpf"
                name="cpf"
                required
                inputmode="numeric"
                maxlength="11">
        </div>

        <div class="campo">
            <label for="senha">Senha</label>
            <input
                type="password"
                id="senha"
                name="senha"
                required>
        </div>

        <button type="submit">Entrar</button>

        <p><a href="/esqueci_senha.php">Esqueci minha senha</a></p>

    </form>

</body>

</html>