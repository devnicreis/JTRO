<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/GrupoFamiliarRepository.php';
require_once __DIR__ . '/../src/Views/helpers.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

$repo = new GrupoFamiliarRepository();

$filtros = [
    'id' => trim($_GET['id'] ?? ''),
    'nome' => trim($_GET['nome'] ?? ''),
    'dia_semana' => trim($_GET['dia_semana'] ?? ''),
    'horario' => trim($_GET['horario'] ?? ''),
    'lideres' => trim($_GET['lideres'] ?? ''),
    'membros' => trim($_GET['membros'] ?? ''),
    'perfil_grupo' => trim($_GET['perfil_grupo'] ?? ''),
    'local_padrao' => trim($_GET['local_padrao'] ?? ''),
    'local_fixo' => trim($_GET['local_fixo'] ?? ''),
    'item_celeiro' => trim($_GET['item_celeiro'] ?? ''),
    'domingo_oracao_culto' => trim($_GET['domingo_oracao_culto'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
];

$grupos = $repo->listarTodos($filtros);
$formato = strtolower(trim($_GET['formato'] ?? 'pdf'));
if (!in_array($formato, ['pdf', 'xls'], true)) {
    $formato = 'pdf';
}

$domingosFiltro = [1 => '1º Domingo', 2 => '2º Domingo', 3 => '3º Domingo', 4 => '4º Domingo', 5 => '5º Domingo'];

function e(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

if ($formato === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="gfs-cadastrados-' . date('Ymd-His') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>GFs Cadastrados</title>
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

<h1>GFs Cadastrados</h1>
<p>Total filtrado: <?php echo (int) count($grupos); ?> GF<?php echo count($grupos) !== 1 ? 's' : ''; ?>.</p>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Dia</th>
        <th>Horario</th>
        <th>Perfil</th>
        <th>Local padrao</th>
        <th>Local fixo</th>
        <th>Celeiro</th>
        <th>Dom. oracao</th>
        <th>Lideres</th>
        <th>Total membros</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php if (count($grupos) === 0): ?>
        <tr><td colspan="12">Nenhum GF encontrado.</td></tr>
    <?php endif; ?>
    <?php foreach ($grupos as $grupo): ?>
        <tr>
            <td><?php echo (int) $grupo['id']; ?></td>
            <td><?php echo e((string) ($grupo['nome'] ?? '')); ?></td>
            <td><?php echo e(ucfirst((string) ($grupo['dia_semana'] ?? ''))); ?></td>
            <td><?php echo e((string) ($grupo['horario'] ?? '')); ?></td>
            <td><?php echo e(labelPerfilGrupo($grupo['perfil_grupo'] ?? null)); ?></td>
            <td><?php echo !empty($grupo['local_padrao']) ? e((string) $grupo['local_padrao']) : '&mdash;'; ?></td>
            <td><?php echo e(labelSimNao((int) ($grupo['local_fixo'] ?? 0))); ?></td>
            <td><?php echo !empty($grupo['item_celeiro']) ? e((string) $grupo['item_celeiro']) : '&mdash;'; ?></td>
            <td><?php echo !empty($grupo['domingo_oracao_culto']) ? e((string) ($domingosFiltro[(int) $grupo['domingo_oracao_culto']] ?? '&mdash;')) : '&mdash;'; ?></td>
            <td><?php echo !empty($grupo['lideres']) ? e((string) $grupo['lideres']) : '&mdash;'; ?></td>
            <td><?php echo (int) ($grupo['total_membros'] ?? 0); ?></td>
            <td><?php echo (int) ($grupo['ativo'] ?? 0) === 1 ? 'Ativo' : 'Desativado'; ?></td>
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
