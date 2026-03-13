<?php

require_once __DIR__ . '/../src/Core/Auth.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

$usuario = Auth::usuario();

$mensagem = '';

if (isset($_GET['senha_alterada']) && $_GET['senha_alterada'] === '1') {
    $mensagem = 'Senha alterada com sucesso.';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>JTRO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body>

    <h1>JTRO</h1>
    <p>Olá, <?php echo htmlspecialchars($usuario['nome']); ?>.</p>

    <ul>
        <?php if ($mensagem !== ''): ?>
            <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <?php if (Auth::isAdmin()): ?>
            <li><a href="/pessoas.php">Cadastro de Pessoas</a></li>
            <li><a href="/grupos_familiares.php">Cadastro de Grupos Familiares</a></li>
        <?php endif; ?>

        <li><a href="/presencas.php">Reuniões e Presenças</a></li>
    </ul>

    <li><a href="/meu_perfil.php">Meu Perfil</a></li>
    <li><a href="/logout.php">Sair</a></li>

</body>

</html>