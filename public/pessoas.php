<?php

require_once __DIR__ . '/../src/Models/Pessoa.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';

$repo = new PessoaRepository();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $cargo = $_POST['cargo'] ?? '';

    $nome = preg_replace('/\s+/', ' ', $nome);

    if ($nome === '' || $cpf === '' || $cargo === '') {
        $erro = 'Preencha nome, CPF e cargo.';
    } elseif (!preg_match('/^[\p{L}\s]+$/u', $nome)) {
        $erro = 'O nome deve conter apenas letras e espaços.';
    } elseif (!ctype_digit($cpf)) {
        $erro = 'O CPF deve conter somente números, sem pontos e traços.';
    } elseif (strlen($cpf) !== 11) {
        $erro = 'O CPF deve conter exatamente 11 dígitos.';
    } elseif ($repo->buscarPorCpf($cpf) !== null) {
        $erro = 'Já existe uma pessoa cadastrada com esse CPF.';
    } else {
        try {
            $pessoa = new Pessoa($nome, $cpf, $cargo);
            $repo->salvar($pessoa);
            $mensagem = 'Pessoa cadastrada com sucesso.';
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

$pessoas = $repo->listarTodas();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Pessoas - JTRO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 24px auto;
            padding: 0 16px;
        }

        h1, h2 {
            margin-bottom: 12px;
        }

        form {
            border: 1px solid #ccc;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .campo {
            margin-bottom: 12px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input, select, button {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        small {
            display: block;
            margin-top: 6px;
            color: #555;
        }

        button {
            cursor: pointer;
        }

        .mensagem {
            background: #e8f5e9;
            color: #1b5e20;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .erro {
            background: #ffebee;
            color: #b71c1c;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f3f3f3;
        }

        .menu {
            margin-bottom: 20px;
        }

        .form-excluir {
            margin: 0;
            padding: 0;
            border: none;
        }

        .botao-excluir {
            background: #c62828;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
        }
    </style>
</head>
<body>

<div class="menu">
    <a href="/index.php">← Voltar para início</a>
</div>

<h1>Cadastro de Pessoas</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/pessoas.php">
    <div class="campo">
        <label for="nome">Nome</label>
        <input
            type="text"
            id="nome"
            name="nome"
            required
            pattern="^[A-Za-zÀ-ÿ\s]+$"
            title="Digite apenas letras e espaços."
            value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
        >
        <small>Digite somente letras e espaços.</small>
    </div>

    <div class="campo">
        <label for="cpf">CPF</label>
        <input
            type="text"
            id="cpf"
            name="cpf"
            required
            inputmode="numeric"
            maxlength="11"
            pattern="\d{11}"
            title="Digite somente números, sem pontos e traços."
            value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>"
        >
        <small>Digite somente números, sem pontos e traços.</small>
    </div>

    <div class="campo">
        <label for="cargo">Cargo</label>
        <select id="cargo" name="cargo" required>
            <option value="">Selecione</option>
            <option value="membro" <?php echo (($_POST['cargo'] ?? '') === 'membro') ? 'selected' : ''; ?>>Membro</option>
            <option value="lider" <?php echo (($_POST['cargo'] ?? '') === 'lider') ? 'selected' : ''; ?>>Líder</option>
            <option value="admin" <?php echo (($_POST['cargo'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
        </select>
    </div>

    <button type="submit">Cadastrar pessoa</button>
</form>

<h2>Pessoas cadastradas</h2>

<?php if (count($pessoas) === 0): ?>
    <p>Nenhuma pessoa cadastrada ainda.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Cargo</th>
                <th>Ativo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pessoas as $registro): ?>
                <tr>
                    <td><?php echo htmlspecialchars($registro['id']); ?></td>
                    <td><?php echo htmlspecialchars($registro['nome']); ?></td>
                    <td><?php echo htmlspecialchars($registro['cpf']); ?></td>
                    <td><?php echo htmlspecialchars($registro['cargo']); ?></td>
                    <td><?php echo (int)$registro['ativo'] === 1 ? 'Sim' : 'Não'; ?></td>
                    <td>
                        <form
                            method="POST"
                            action="/pessoas_excluir.php"
                            class="form-excluir"
                            onsubmit="return confirm('Deseja realmente excluir esta pessoa?');"
                        >
                            <input type="hidden" name="id" value="<?php echo $registro['id']; ?>">
                            <button type="submit" class="botao-excluir">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<script>
    const nomeInput = document.getElementById('nome');
    const cpfInput = document.getElementById('cpf');

    nomeInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^A-Za-zÀ-ÿ\s]/g, '');
    });

    cpfInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 11);
    });
</script>

</body>
</html>