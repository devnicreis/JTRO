<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Views/helpers.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

$pessoaRepo = new PessoaRepository();
$grupoRepo = new GrupoFamiliarRepository();

$filtros = [
    'id' => trim($_GET['id'] ?? ''),
    'nome' => trim($_GET['nome'] ?? ''),
    'cpf' => trim($_GET['cpf'] ?? ''),
    'email' => trim($_GET['email'] ?? ''),
    'cargo' => trim($_GET['cargo'] ?? ''),
    'genero' => trim($_GET['genero'] ?? ''),
    'data_nascimento' => trim($_GET['data_nascimento'] ?? ''),
    'idade_min' => trim($_GET['idade_min'] ?? ''),
    'idade_max' => trim($_GET['idade_max'] ?? ''),
    'estado_civil' => trim($_GET['estado_civil'] ?? ''),
    'nome_conjuge' => trim($_GET['nome_conjuge'] ?? ''),
    'eh_lider' => trim($_GET['eh_lider'] ?? ''),
    'lider_grupo_familiar' => trim($_GET['lider_grupo_familiar'] ?? ''),
    'lider_departamento' => trim($_GET['lider_departamento'] ?? ''),
    'grupo_familiar_id' => trim($_GET['grupo_familiar_id'] ?? ''),
    'telefone' => trim($_GET['telefone'] ?? ''),
    'endereco' => trim($_GET['endereco'] ?? ''),
    'concluiu_integracao' => trim($_GET['concluiu_integracao'] ?? ''),
    'participou_retiro_integracao' => trim($_GET['participou_retiro_integracao'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
];

$pessoas = $pessoaRepo->listarTodos($filtros);
$formato = strtolower(trim($_GET['formato'] ?? 'pdf'));
if (!in_array($formato, ['pdf', 'xls'], true)) {
    $formato = 'pdf';
}

function e(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

if ($formato === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="pessoas-cadastradas-' . date('Ymd-His') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pessoas Cadastradas</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #222; }
        h1 { margin: 0 0 8px; font-size: 20px; }
        p { margin: 0 0 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d0d0d0; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f4f4f4; font-size: 11px; text-transform: uppercase; }
        .acoes-impressao { margin-bottom: 14px; }
        @media print {
            .acoes-impressao { display: none; }
            body { margin: 8px; }
        }
    </style>
</head>
<body>
<?php if ($formato === 'pdf'): ?>
    <div class="acoes-impressao">
        <button type="button" onclick="window.print()">Imprimir / Salvar PDF</button>
    </div>
<?php endif; ?>

<h1>Pessoas Cadastradas</h1>
<p>Total filtrado: <?php echo (int) count($pessoas); ?> pessoa<?php echo count($pessoas) !== 1 ? 's' : ''; ?>.</p>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>CPF</th>
        <th>E-mail</th>
        <th>Perfil</th>
        <th>Data nasc.</th>
        <th>Idade</th>
        <th>GF</th>
        <th>Contato</th>
        <th>Endereco</th>
        <th>Integracao</th>
        <th>Retiro</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php if (count($pessoas) === 0): ?>
        <tr><td colspan="13">Nenhuma pessoa encontrada.</td></tr>
    <?php endif; ?>
    <?php foreach ($pessoas as $pessoa): ?>
        <?php $idade = calcularIdade($pessoa['data_nascimento'] ?? null); ?>
        <tr>
            <td><?php echo (int) $pessoa['id']; ?></td>
            <td><?php echo e((string) ($pessoa['nome'] ?? '')); ?></td>
            <td><?php echo e((string) ($pessoa['cpf'] ?? '')); ?></td>
            <td><?php echo !empty($pessoa['email']) ? e((string) $pessoa['email']) : '&mdash;'; ?></td>
            <td><?php echo e(ucfirst((string) ($pessoa['cargo'] ?? ''))); ?></td>
            <td><?php echo e(formatarDataBr($pessoa['data_nascimento'] ?? null)); ?></td>
            <td><?php echo $idade !== null ? e((string) $idade . ' anos') : '&mdash;'; ?></td>
            <td><?php echo !empty($pessoa['grupo_familiar_nome']) ? e((string) $pessoa['grupo_familiar_nome']) : '&mdash;'; ?></td>
            <td>
                Fixo: <?php echo e(formatarTelefone($pessoa['telefone_fixo'] ?? null)); ?><br>
                Movel: <?php echo e(formatarTelefone($pessoa['telefone_movel'] ?? null)); ?>
            </td>
            <td><?php echo e(formatarEnderecoPessoa($pessoa)); ?></td>
            <td><?php echo e(labelSimNao((int) ($pessoa['concluiu_integracao'] ?? 0))); ?></td>
            <td><?php echo e(labelSimNao((int) ($pessoa['participou_retiro_integracao'] ?? 0))); ?></td>
            <td><?php echo (int) ($pessoa['ativo'] ?? 0) === 1 ? 'Ativo' : 'Desativado'; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ($formato === 'pdf'): ?>
<script>
window.addEventListener('load', function () {
    window.print();
});
</script>
<?php endif; ?>
</body>
</html>
