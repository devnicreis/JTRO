<?php

require_once __DIR__ . '/../src/Repositories/ReuniaoRepository.php';

$repo = new ReuniaoRepository();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grupoFamiliarId = (int) ($_POST['grupo_familiar_id'] ?? 0);
    $data = trim($_POST['data'] ?? '');
    $horario = trim($_POST['horario'] ?? '');
    $local = trim($_POST['local'] ?? '');
    $motivoAlteracao = trim($_POST['motivo_alteracao'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    try {
        $repo->salvar(
            $grupoFamiliarId,
            $data,
            $horario,
            $local,
            $motivoAlteracao,
            $observacoes
        );

        $mensagem = 'Reunião cadastrada com sucesso.';
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

$grupos = $repo->listarGruposFamiliares();
$reunioes = $repo->listarTodas();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Reuniões - JTRO</title>
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

        input, select, textarea, button {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
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

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
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

<h1>Cadastro de Reuniões</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/reunioes.php">
    <div class="campo">
        <label for="grupo_familiar_id">Grupo Familiar</label>
        <select id="grupo_familiar_id" name="grupo_familiar_id" required>
            <option value="">Selecione</option>
            <?php foreach ($grupos as $grupo): ?>
                <option
                    value="<?php echo $grupo['id']; ?>"
                    <?php echo ((string)($grupo['id']) === ($_POST['grupo_familiar_id'] ?? '')) ? 'selected' : ''; ?>
                >
                    <?php echo htmlspecialchars($grupo['nome']); ?> — <?php echo htmlspecialchars($grupo['dia_semana']); ?> às <?php echo htmlspecialchars($grupo['horario']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="data">Data</label>
            <input
                type="date"
                id="data"
                name="data"
                required
                value="<?php echo htmlspecialchars($_POST['data'] ?? ''); ?>"
            >
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

    <div class="campo">
        <label for="local">Local</label>
        <input
            type="text"
            id="local"
            name="local"
            required
            value="<?php echo htmlspecialchars($_POST['local'] ?? ''); ?>"
        >
    </div>

    <div class="campo">
        <label for="motivo_alteracao">Motivo da alteração (opcional)</label>
        <input
            type="text"
            id="motivo_alteracao"
            name="motivo_alteracao"
            value="<?php echo htmlspecialchars($_POST['motivo_alteracao'] ?? ''); ?>"
        >
    </div>

    <div class="campo">
        <label for="observacoes">Observações (opcional)</label>
        <textarea
            id="observacoes"
            name="observacoes"
        ><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
    </div>

    <button type="submit">Cadastrar Reunião</button>
</form>

<h2>Reuniões cadastradas</h2>

<?php if (count($reunioes) === 0): ?>
    <p>Nenhuma reunião cadastrada ainda.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Grupo Familiar</th>
                <th>Data</th>
                <th>Horário</th>
                <th>Local</th>
                <th>Motivo da alteração</th>
                <th>Observações</th>
                <th>Presenças</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reunioes as $reuniao): ?>
                <tr>
                    <td><?php echo htmlspecialchars($reuniao['id']); ?></td>
                    <td><?php echo htmlspecialchars($reuniao['grupo_nome']); ?></td>
                    <td><?php echo htmlspecialchars($reuniao['data']); ?></td>
                    <td><?php echo htmlspecialchars($reuniao['horario']); ?></td>
                    <td><?php echo htmlspecialchars($reuniao['local']); ?></td>
                    <td><?php echo htmlspecialchars($reuniao['motivo_alteracao'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($reuniao['observacoes'] ?? ''); ?></td>
                    <td>
                        Total: <?php echo htmlspecialchars($reuniao['total_presencas']); ?><br>
                        Presentes: <?php echo htmlspecialchars($reuniao['total_presentes']); ?><br>
                        Ausentes: <?php echo htmlspecialchars($reuniao['total_ausentes']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>