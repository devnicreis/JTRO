<?php

require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';

$repo = new GrupoFamiliarRepository();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $diaSemana = trim($_POST['dia_semana'] ?? '');
    $horario = trim($_POST['horario'] ?? '');
    $lideresIds = $_POST['lideres'] ?? [];
    $membrosIds = $_POST['membros'] ?? [];

    try {
        $repo->salvar($nome, $diaSemana, $horario, $lideresIds, $membrosIds);
        $mensagem = 'Grupo Familiar cadastrado com sucesso.';
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

$pessoas = $repo->listarPessoasAtivas();
$grupos = $repo->listarTodos();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Grupos Familiares - JTRO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
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

        .menu {
            margin-bottom: 20px;
        }

        .checkbox-lista {
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 12px;
            max-height: 220px;
            overflow-y: auto;
            background: #fafafa;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .checkbox-item input {
            width: auto;
            padding: 0;
            margin: 0;
        }

        .checkbox-item label {
            margin: 0;
            font-weight: normal;
            display: inline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f3f3;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 800px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="menu">
    <a href="/index.php">← Voltar para início</a>
</div>

<h1>Cadastro de Grupos Familiares</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/grupos_familiares.php">
    <div class="campo">
        <label for="nome">Nome do Grupo Familiar</label>
        <input
            type="text"
            id="nome"
            name="nome"
            required
            value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
        >
    </div>

    <div class="grid">
        <div class="campo">
            <label for="dia_semana">Dia da semana</label>
            <select id="dia_semana" name="dia_semana" required>
                <option value="">Selecione</option>
                <?php
                $dias = ['segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
                $diaSelecionado = $_POST['dia_semana'] ?? '';
                foreach ($dias as $dia):
                ?>
                    <option value="<?php echo $dia; ?>" <?php echo $diaSelecionado === $dia ? 'selected' : ''; ?>>
                        <?php echo ucfirst($dia); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="horario">Horário</label>
            <input
                type="time"
                id="horario"
                name="horario"
                required
                value="<?php echo htmlspecialchars($_POST['horario'] ?? ''); ?>"
            >
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label>Líderes</label>
            <div class="checkbox-lista">
                <?php foreach ($pessoas as $pessoa): ?>
                    <div class="checkbox-item">
                        <input
                            type="checkbox"
                            id="lider_<?php echo $pessoa['id']; ?>"
                            name="lideres[]"
                            value="<?php echo $pessoa['id']; ?>"
                            <?php echo in_array((string)$pessoa['id'], $_POST['lideres'] ?? [], true) ? 'checked' : ''; ?>
                        >
                        <label for="lider_<?php echo $pessoa['id']; ?>">
                            <?php echo htmlspecialchars($pessoa['nome']); ?> (<?php echo htmlspecialchars($pessoa['cargo']); ?>)
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="campo">
            <label>Membros</label>
            <div class="checkbox-lista">
                <?php foreach ($pessoas as $pessoa): ?>
                    <div class="checkbox-item">
                        <input
                            type="checkbox"
                            id="membro_<?php echo $pessoa['id']; ?>"
                            name="membros[]"
                            value="<?php echo $pessoa['id']; ?>"
                            <?php echo in_array((string)$pessoa['id'], $_POST['membros'] ?? [], true) ? 'checked' : ''; ?>
                        >
                        <label for="membro_<?php echo $pessoa['id']; ?>">
                            <?php echo htmlspecialchars($pessoa['nome']); ?> (<?php echo htmlspecialchars($pessoa['cargo']); ?>)
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <button type="submit">Cadastrar Grupo Familiar</button>
</form>

<h2>Grupos Familiares cadastrados</h2>

<?php if (count($grupos) === 0): ?>
    <p>Nenhum Grupo Familiar cadastrado ainda.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Dia</th>
                <th>Horário</th>
                <th>Líderes</th>
                <th>Total de membros</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grupos as $grupo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($grupo['id']); ?></td>
                    <td><?php echo htmlspecialchars($grupo['nome']); ?></td>
                    <td><?php echo htmlspecialchars($grupo['dia_semana']); ?></td>
                    <td><?php echo htmlspecialchars($grupo['horario']); ?></td>
                    <td><?php echo htmlspecialchars($grupo['lideres'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($grupo['total_membros']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>