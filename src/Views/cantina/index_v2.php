<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
$diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
$anosDisponiveis = range((int) date('Y') - 1, (int) date('Y') + 2);
$meses = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro',
];
$escalasPorMes = [];
foreach ($meses as $numeroMes => $_nomeMes) {
    $escalasPorMes[$numeroMes] = [];
}
foreach ($escalas as $escalaItem) {
    $mesEscala = (int) substr((string) ($escalaItem['data_escala'] ?? ''), 5, 2);
    if (isset($escalasPorMes[$mesEscala])) {
        $escalasPorMes[$mesEscala][] = $escalaItem;
    }
}
$filtroPorData = trim((string) ($filtros['data_escala'] ?? '')) !== '';
$filtroPorGrupo = trim((string) ($filtros['grupo_familiar_id'] ?? '')) !== '';
$mesAtual = (int) date('n');
$mostrarSomenteMesesComRegistros = $filtroPorData || $filtroPorGrupo;
?>

<div class="page-header">
    <h1>Cantina</h1>
    <p class="page-header-subtitulo">Filtre por GF ou por data e gerencie as escalas em uma grade mais compacta, com rolagem apenas vertical.</p>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/cantina.php" class="form-secao">
    <input type="hidden" name="acao" value="salvar">
    <input type="hidden" name="ano" value="<?php echo (int) $ano; ?>">
    <div class="form-secao-titulo">Adicionar escala avulsa</div>
    <div class="grid" style="grid-template-columns: 160px 1.4fr 1fr auto;">
        <div class="campo">
            <label for="data_escala_extra">Data</label>
            <input type="date" id="data_escala_extra" name="data_escala" required>
        </div>
        <div class="campo">
            <label for="grupo_familiar_extra">Grupo Familiar</label>
            <select id="grupo_familiar_extra" name="grupo_familiar_id" required>
                <option value="">Selecione</option>
                <?php foreach ($gruposFamiliares as $grupo): ?>
                    <option value="<?php echo (int) $grupo['id']; ?>"><?php echo htmlspecialchars($grupo['nome']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="observacoes_extra">Observações</label>
            <input type="text" id="observacoes_extra" name="observacoes" placeholder="Opcional">
        </div>
        <div class="campo" style="display:flex; align-items:flex-end;">
            <button type="submit">Adicionar</button>
        </div>
    </div>
</form>

<form method="GET" action="/cantina.php" id="filtrosCantinaTabela"></form>

<div class="tabela-wrapper tabela-listagem-limitada cantina-listagem-limitada">
    <table class="tabela-cantina cantina-tabela-enxuta">
        <thead>
            <tr>
                <th>Data</th>
                <th>Dia</th>
                <th>GF</th>
                <th>Observações</th>
                <th>Origem</th>
                <th class="tabela-acoes">Ações</th>
            </tr>
            <tr class="filtros-linha">
                <th>
                    <input class="tabela-filtro-campo" form="filtrosCantinaTabela" type="date" name="data_escala" value="<?php echo htmlspecialchars($filtros['data_escala'] ?? ''); ?>">
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosCantinaTabela" name="ano">
                        <?php foreach ($anosDisponiveis as $anoOpcao): ?>
                            <option value="<?php echo (int) $anoOpcao; ?>" <?php echo $anoOpcao === $ano ? 'selected' : ''; ?>>
                                <?php echo (int) $anoOpcao; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosCantinaTabela" name="grupo_familiar_id">
                        <option value="">Todos</option>
                        <?php foreach ($gruposFamiliares as $grupo): ?>
                            <option value="<?php echo (int) $grupo['id']; ?>" <?php echo (($filtros['grupo_familiar_id'] ?? '') === (string) $grupo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grupo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th></th>
                <th></th>
                <th>
                    <div class="tabela-filtro-acoes">
                        <button type="submit" form="filtrosCantinaTabela">Filtrar</button>
                        <a class="botao-link botao-secundario" href="/cantina.php?ano=<?php echo (int) $ano; ?>">Limpar</a>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($escalas) === 0): ?>
                <tr>
                    <td colspan="6" class="tabela-vazia">Nenhuma escala encontrada para os filtros atuais.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($meses as $numeroMes => $nomeMes): ?>
                <?php $escalasDoMes = $escalasPorMes[$numeroMes] ?? []; ?>
                <?php if ($mostrarSomenteMesesComRegistros && count($escalasDoMes) === 0): ?>
                    <?php continue; ?>
                <?php endif; ?>

                <tr class="cantina-mes-separador">
                    <td colspan="6">
                        <div class="cantina-mes-separador-conteudo">
                            <div class="cantina-mes-separador-titulo">
                                <button type="button" class="cantina-mes-toggle" data-mes="<?php echo (int) $numeroMes; ?>" aria-expanded="true" title="Ocultar mês">
                                    <span class="cantina-mes-toggle-icone">▾</span>
                                </button>
                                <span><?php echo htmlspecialchars($nomeMes); ?></span>
                            </div>
                            <span class="badge badge-blue"><?php echo count($escalasDoMes); ?></span>
                        </div>
                    </td>
                </tr>

                <?php if (count($escalasDoMes) === 0): ?>
                    <tr class="cantina-mes-linha" data-mes="<?php echo (int) $numeroMes; ?>">
                        <td colspan="6" class="tabela-vazia">Nenhuma escala neste mês.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($escalasDoMes as $escala): ?>
                    <?php
                    $dataEscala = (string) $escala['data_escala'];
                    $dt = DateTime::createFromFormat('Y-m-d', $dataEscala);
                    $diaSemana = $dt ? $diasSemana[(int) $dt->format('w')] : '—';
                    $origem = $escala['origem'] === 'data_alterada' ? 'Data alterada' : ($escala['origem'] === 'salva' ? 'Escala salva' : 'Domingo padrão');
                    $formId = 'form-cantina-' . preg_replace('/[^a-z0-9]+/i', '-', $dataEscala) . '-' . (int) ($escala['id'] ?? 0);
                    ?>
                    <tr class="cantina-mes-linha <?php echo $numeroMes === $mesAtual ? 'cantina-linha-mes-atual' : ''; ?>" data-mes="<?php echo (int) $numeroMes; ?>">
                        <td>
                            <input type="date" name="data_escala" value="<?php echo htmlspecialchars($dataEscala); ?>" required form="<?php echo htmlspecialchars($formId); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($diaSemana); ?></td>
                        <td>
                            <select name="grupo_familiar_id" required form="<?php echo htmlspecialchars($formId); ?>">
                                <option value="">Selecione</option>
                                <?php foreach ($gruposFamiliares as $grupo): ?>
                                    <option value="<?php echo (int) $grupo['id']; ?>" <?php echo ((int) ($escala['grupo_familiar_id'] ?? 0) === (int) $grupo['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($grupo['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="observacoes" value="<?php echo htmlspecialchars($escala['observacoes'] ?? ''); ?>" placeholder="Opcional" form="<?php echo htmlspecialchars($formId); ?>">
                        </td>
                        <td>
                            <?php if ($escala['origem'] === 'data_alterada'): ?>
                                <span class="badge badge-amber"><?php echo htmlspecialchars($origem); ?></span>
                            <?php elseif ($escala['origem'] === 'salva'): ?>
                                <span class="badge badge-green"><?php echo htmlspecialchars($origem); ?></span>
                            <?php else: ?>
                                <span class="badge badge-blue"><?php echo htmlspecialchars($origem); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="tabela-acoes">
                            <div class="acoes acoes-tabela-inline cantina-acoes-inline">
                                <button type="submit" class="btn-gf btn-gf-editar" form="<?php echo htmlspecialchars($formId); ?>">Salvar</button>
                                <?php if ((int) ($escala['id'] ?? 0) > 0): ?>
                                    <form method="POST" action="/cantina.php" class="form-inline-tabela" onsubmit="return confirm('Deseja remover esta escala da cantina?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="ano" value="<?php echo (int) $ano; ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $escala['id']; ?>">
                                        <button type="submit" class="btn-gf btn-gf-desativar">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php foreach ($escalas as $escala): ?>
    <?php $formId = 'form-cantina-' . preg_replace('/[^a-z0-9]+/i', '-', (string) $escala['data_escala']) . '-' . (int) ($escala['id'] ?? 0); ?>
    <form method="POST" action="/cantina.php" id="<?php echo htmlspecialchars($formId); ?>" class="form-inline-tabela">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="ano" value="<?php echo (int) $ano; ?>">
        <input type="hidden" name="id" value="<?php echo (int) ($escala['id'] ?? 0); ?>">
    </form>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.cantina-mes-toggle');
    if (!toggles.length) return;

    const storageKey = 'cantina-meses-ocultos-' + <?php echo json_encode((string) $ano); ?>;
    let mesesOcultos = {};

    try {
        mesesOcultos = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
    } catch (error) {
        mesesOcultos = {};
    }

    function salvarEstado() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(mesesOcultos));
        } catch (error) {
            // Sem persistência quando localStorage não estiver disponível.
        }
    }

    function aplicarVisibilidadeMes(mes, oculto) {
        const linhas = document.querySelectorAll('.cantina-mes-linha[data-mes="' + mes + '"]');
        linhas.forEach(function(linha) {
            linha.classList.toggle('cantina-mes-linha-oculta', oculto);
        });

        const botao = document.querySelector('.cantina-mes-toggle[data-mes="' + mes + '"]');
        if (!botao) return;
        botao.setAttribute('aria-expanded', oculto ? 'false' : 'true');
        botao.title = oculto ? 'Mostrar mês' : 'Ocultar mês';
        const icone = botao.querySelector('.cantina-mes-toggle-icone');
        if (icone) {
            icone.textContent = oculto ? '▸' : '▾';
        }
    }

    toggles.forEach(function(botao) {
        const mes = botao.getAttribute('data-mes');
        if (!mes) return;

        if (mesesOcultos[mes]) {
            aplicarVisibilidadeMes(mes, true);
        }

        botao.addEventListener('click', function() {
            const ocultoAtual = !!mesesOcultos[mes];
            const novoOculto = !ocultoAtual;
            mesesOcultos[mes] = novoOculto;
            aplicarVisibilidadeMes(mes, novoOculto);
            salvarEstado();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
