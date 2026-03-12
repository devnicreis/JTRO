<?php

require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';

$repo = new PresencaRepository();

$mensagem = '';
$erro = '';

$reuniaoId = (int) ($_GET['reuniao_id'] ?? $_POST['reuniao_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $presencas = $_POST['presencas'] ?? [];

    try {
        $repo->atualizarEmLote($presencas);
        $mensagem = 'Presenças atualizadas com sucesso.';
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

$reunioes = $repo->listarReunioes();
$listaPresencas = [];

if ($reuniaoId > 0) {
    $listaPresencas = $repo->listarPresencasPorReuniao($reuniaoId);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Presenças - JTRO</title>
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

        select, button {
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
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

        .status-group {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .status-group label {
            margin: 0;
            font-weight: normal;
            display: inline;
        }

        .top-form {
            margin-bottom: 20px;
        }

        .botao-salvar {
            margin-top: 16px;
        }
    </style>
</head>
<body>

<div class="menu">
    <a href="/index.php">← Voltar para início</a>
</div>

<h1>Presenças</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="GET" action="/presencas.php" class="top-form">
    <div class="campo">
        <label for="reuniao_id">Selecione a reunião</label>
        <select id="reuniao_id" name="reuniao_id" required onchange="this.form.submit()">
            <option value="">Selecione</option>
            <?php foreach ($reunioes as $reuniao): ?>
                <option
                    value="<?php echo $reuniao['id']; ?>"
                    <?php echo $reuniaoId === (int)$reuniao['id'] ? 'selected' : ''; ?>
                >
                    <?php
                    echo htmlspecialchars(
                        $reuniao['grupo_nome'] . ' - ' .
                        $reuniao['data'] . ' às ' .
                        $reuniao['horario'] . ' - ' .
                        $reuniao['local']
                    );
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($reuniaoId > 0 && count($listaPresencas) > 0): ?>
    <form method="POST" action="/presencas.php">
        <input type="hidden" name="reuniao_id" value="<?php echo $reuniaoId; ?>">

        <h2>Participantes da reunião</h2>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Cargo</th>
                    <th>Presença</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listaPresencas as $presenca): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($presenca['nome']); ?></td>
                        <td><?php echo htmlspecialchars($presenca['cpf']); ?></td>
                        <td><?php echo htmlspecialchars($presenca['cargo']); ?></td>
                        <td>
                            <div class="status-group">
                                <label>
                                    <input
                                        type="radio"
                                        name="presencas[<?php echo $presenca['id']; ?>]"
                                        value="presente"
                                        <?php echo $presenca['status'] === 'presente' ? 'checked' : ''; ?>
                                    >
                                    Presente
                                </label>

                                <label>
                                    <input
                                        type="radio"
                                        name="presencas[<?php echo $presenca['id']; ?>]"
                                        value="ausente"
                                        <?php echo $presenca['status'] === 'ausente' ? 'checked' : ''; ?>
                                    >
                                    Ausente
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="botao-salvar">Salvar presenças</button>
    </form>
<?php elseif ($reuniaoId > 0): ?>
    <p>Essa reunião não possui presenças cadastradas.</p>
<?php endif; ?>

</body>
</html>