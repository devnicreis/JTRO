<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
$estadosCivis = opcoesEstadoCivil();
$generos = opcoesGenero();
$idadeMinLimite = 0;
$idadeMaxLimite = 120;
$idadeMinAtual = ($filtros['idade_min'] ?? '') !== '' ? max($idadeMinLimite, (int) $filtros['idade_min']) : $idadeMinLimite;
$idadeMaxAtual = ($filtros['idade_max'] ?? '') !== '' ? min($idadeMaxLimite, (int) $filtros['idade_max']) : $idadeMaxLimite;
if ($idadeMinAtual > $idadeMaxAtual) {
    [$idadeMinAtual, $idadeMaxAtual] = [$idadeMaxAtual, $idadeMinAtual];
}
?>

<div class="page-header">
    <h1>Pessoas Cadastradas</h1>
    <p class="page-header-subtitulo">Use a rolagem da tabela para consultar os cadastros sem alongar a p&aacute;gina inteira.</p>
</div>

<div class="acoes" style="margin-bottom: 16px;">
    <a href="/pessoas.php" class="botao-link botao-secundario">Ir para cadastro</a>
</div>

<form method="GET" action="/pessoas_cadastradas.php" id="filtrosPessoasTabela"></form>
<div class="tabela-wrapper tabela-listagem-limitada">
    <table class="tabela-pessoas">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Data de nasc.</th>
                <th>Idade</th>
                <th>Estado civil</th>
                <th>G&ecirc;nero</th>
                <th>C&ocirc;njuge</th>
                <th>&Eacute; l&iacute;der</th>
                <th>L&iacute;der GF</th>
                <th>L&iacute;der Dpto.</th>
                <th>GF</th>
                <th>Contato</th>
                <th>Endere&ccedil;o</th>
                <th>Integra&ccedil;&atilde;o</th>
                <th>Retiro</th>
                <th>Status</th>
                <th class="tabela-acoes">A&ccedil;&otilde;es</th>
            </tr>
            <tr class="filtros-linha">
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="id" value="<?php echo htmlspecialchars($filtros['id'] ?? ''); ?>" placeholder="ID"></th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="nome" value="<?php echo htmlspecialchars($filtros['nome'] ?? ''); ?>" placeholder="Nome"></th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="cpf" value="<?php echo htmlspecialchars($filtros['cpf'] ?? ''); ?>" placeholder="CPF"></th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="email" value="<?php echo htmlspecialchars($filtros['email'] ?? ''); ?>" placeholder="E-mail"></th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="cargo">
                        <option value="">Todos</option>
                        <option value="membro" <?php echo (($filtros['cargo'] ?? '') === 'membro') ? 'selected' : ''; ?>>Membro</option>
                        <option value="admin" <?php echo (($filtros['cargo'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="date" name="data_nascimento" value="<?php echo htmlspecialchars($filtros['data_nascimento'] ?? ''); ?>"></th>
                <th>
                    <div class="idade-range-wrap" data-min="<?php echo (int) $idadeMinLimite; ?>" data-max="<?php echo (int) $idadeMaxLimite; ?>">
                        <input type="hidden" form="filtrosPessoasTabela" name="idade_min" id="idade_min" value="<?php echo htmlspecialchars((string) ($filtros['idade_min'] ?? '')); ?>">
                        <input type="hidden" form="filtrosPessoasTabela" name="idade_max" id="idade_max" value="<?php echo htmlspecialchars((string) ($filtros['idade_max'] ?? '')); ?>">
                        <div class="idade-range-track"></div>
                        <div class="idade-range-progress" id="idade-range-progress"></div>
                        <input type="range" id="idade-min-range" min="<?php echo (int) $idadeMinLimite; ?>" max="<?php echo (int) $idadeMaxLimite; ?>" value="<?php echo (int) $idadeMinAtual; ?>">
                        <input type="range" id="idade-max-range" min="<?php echo (int) $idadeMinLimite; ?>" max="<?php echo (int) $idadeMaxLimite; ?>" value="<?php echo (int) $idadeMaxAtual; ?>">
                    </div>
                    <div class="idade-range-valores" id="idade-range-valores"></div>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="estado_civil">
                        <option value="">Todos</option>
                        <?php foreach ($estadosCivis as $valor => $label): ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($filtros['estado_civil'] ?? '') === $valor) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="genero">
                        <option value="">Todos</option>
                        <?php foreach ($generos as $valor => $label): ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($filtros['genero'] ?? '') === $valor) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="nome_conjuge" value="<?php echo htmlspecialchars($filtros['nome_conjuge'] ?? ''); ?>" placeholder="Cônjuge"></th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="eh_lider">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($filtros['eh_lider'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?php echo (($filtros['eh_lider'] ?? '') === '0') ? 'selected' : ''; ?>>N&atilde;o</option>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="lider_grupo_familiar">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($filtros['lider_grupo_familiar'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?php echo (($filtros['lider_grupo_familiar'] ?? '') === '0') ? 'selected' : ''; ?>>N&atilde;o</option>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="lider_departamento">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($filtros['lider_departamento'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?php echo (($filtros['lider_departamento'] ?? '') === '0') ? 'selected' : ''; ?>>N&atilde;o</option>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="grupo_familiar_id">
                        <option value="">Todos</option>
                        <?php foreach ($gruposFamiliares as $grupo): ?>
                            <option value="<?php echo (int) $grupo['id']; ?>" <?php echo (($filtros['grupo_familiar_id'] ?? '') === (string) $grupo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grupo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="telefone" value="<?php echo htmlspecialchars($filtros['telefone'] ?? ''); ?>" placeholder="Contato"></th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="endereco" value="<?php echo htmlspecialchars($filtros['endereco'] ?? ''); ?>" placeholder="Endereço"></th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="concluiu_integracao">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($filtros['concluiu_integracao'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?php echo (($filtros['concluiu_integracao'] ?? '') === '0') ? 'selected' : ''; ?>>N&atilde;o</option>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="participou_retiro_integracao">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($filtros['participou_retiro_integracao'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?php echo (($filtros['participou_retiro_integracao'] ?? '') === '0') ? 'selected' : ''; ?>>N&atilde;o</option>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="status">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($filtros['status'] ?? '') === '1') ? 'selected' : ''; ?>>Ativo</option>
                        <option value="0" <?php echo (($filtros['status'] ?? '') === '0') ? 'selected' : ''; ?>>Desativado</option>
                    </select>
                </th>
                <th>
                    <div class="tabela-filtro-acoes">
                        <button type="submit" form="filtrosPessoasTabela">Filtrar</button>
                        <a class="botao-link botao-secundario" href="/pessoas_cadastradas.php">Limpar</a>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($pessoas) === 0): ?>
                <tr>
                    <td colspan="20" class="tabela-vazia">Nenhuma pessoa encontrada para o filtro atual.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($pessoas as $pessoa): ?>
                <tr>
                    <td><?php echo (int) $pessoa['id']; ?></td>
                    <td><?php echo htmlspecialchars($pessoa['nome']); ?></td>
                    <td><?php echo htmlspecialchars($pessoa['cpf']); ?></td>
                    <td><?php echo htmlspecialchars($pessoa['email'] ?: '—'); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst((string) $pessoa['cargo'])); ?></td>
                    <?php $idade = calcularIdade($pessoa['data_nascimento'] ?? null); ?>
                    <td><?php echo htmlspecialchars(formatarDataBr($pessoa['data_nascimento'] ?? null)); ?></td>
                    <td><?php echo $idade !== null ? htmlspecialchars((string) $idade . ' anos') : '&mdash;'; ?></td>
                    <td><?php echo htmlspecialchars(labelEstadoCivil($pessoa['estado_civil'] ?? null)); ?></td>
                    <td><?php echo htmlspecialchars(labelGenero($pessoa['genero'] ?? null)); ?></td>
                    <td><?php echo htmlspecialchars($pessoa['nome_conjuge'] ?: '—'); ?></td>
                    <td><?php echo htmlspecialchars(labelSimNao((int) ($pessoa['eh_lider'] ?? 0))); ?></td>
                    <td><?php echo htmlspecialchars(labelSimNao((int) ($pessoa['lider_grupo_familiar'] ?? 0))); ?></td>
                    <td><?php echo htmlspecialchars(labelSimNao((int) ($pessoa['lider_departamento'] ?? 0))); ?></td>
                    <td><?php echo htmlspecialchars($pessoa['grupo_familiar_nome'] ?: '—'); ?></td>
                    <td>
                        <div class="tabela-contatos">
                            <div class="tabela-contatos-linha">
                                <span class="tabela-contatos-rotulo">Fixo</span>
                                <span><?php echo htmlspecialchars(formatarTelefone($pessoa['telefone_fixo'] ?? null)); ?></span>
                            </div>
                            <div class="tabela-contatos-linha">
                                <span class="tabela-contatos-rotulo">Móvel</span>
                                <span><?php echo htmlspecialchars(formatarTelefone($pessoa['telefone_movel'] ?? null)); ?></span>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars(formatarEnderecoPessoa($pessoa)); ?></td>
                    <td><?php echo htmlspecialchars(labelSimNao((int) ($pessoa['concluiu_integracao'] ?? 0))); ?></td>
                    <td><?php echo htmlspecialchars(labelSimNao((int) ($pessoa['participou_retiro_integracao'] ?? 0))); ?></td>
                    <td>
                        <?php if ((int) $pessoa['ativo'] === 1): ?>
                            <span class="status-ativo">Ativo</span>
                        <?php else: ?>
                            <span class="status-inativo">Desativado</span>
                            <?php if (!empty($pessoa['motivo_desativacao_texto'])): ?>
                                <div class="notif-detalhe"><?php echo htmlspecialchars($pessoa['motivo_desativacao_texto']); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="tabela-acoes">
                        <div class="acoes acoes-tabela-inline" style="flex-direction: column; gap: 6px;">
                            <a class="btn-gf btn-gf-editar" href="/pessoas_editar.php?id=<?php echo (int) $pessoa['id']; ?>">Editar</a>
                            <a class="btn-gf btn-gf-integracao" href="/pessoas_integracao.php?id=<?php echo (int) $pessoa['id']; ?>">Aulas Integração</a>
                            <?php if ((int) $pessoa['ativo'] === 1): ?>
                                <a class="btn-gf btn-gf-desativar" href="/pessoas_desativar.php?id=<?php echo (int) $pessoa['id']; ?>">Desativar</a>
                            <?php else: ?>
                                <form method="POST" action="/pessoas_reativar.php" class="form-acao" onsubmit="return confirm('Deseja reativar esta pessoa?');">
                                    <input type="hidden" name="id" value="<?php echo (int) $pessoa['id']; ?>">
                                    <button type="submit" class="btn-gf btn-gf-reativar">Reativar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrap = document.querySelector('.idade-range-wrap');
    if (!wrap) return;

    const minRange = document.getElementById('idade-min-range');
    const maxRange = document.getElementById('idade-max-range');
    const minInput = document.getElementById('idade_min');
    const maxInput = document.getElementById('idade_max');
    const progress = document.getElementById('idade-range-progress');
    const label = document.getElementById('idade-range-valores');

    if (!minRange || !maxRange || !minInput || !maxInput || !progress || !label) {
        return;
    }

    const hardMin = parseInt(wrap.dataset.min || '0', 10);
    const hardMax = parseInt(wrap.dataset.max || '120', 10);

    function atualizarRangeIdade() {
        let min = parseInt(minRange.value, 10);
        let max = parseInt(maxRange.value, 10);

        if (min > max) {
            const troca = min;
            min = max;
            max = troca;
            minRange.value = String(min);
            maxRange.value = String(max);
        }

        const total = hardMax - hardMin;
        const inicio = ((min - hardMin) / total) * 100;
        const fim = ((max - hardMin) / total) * 100;

        progress.style.left = inicio + '%';
        progress.style.width = (fim - inicio) + '%';

        if (min === hardMin && max === hardMax) {
            minInput.value = '';
            maxInput.value = '';
            label.textContent = 'Todas';
            return;
        }

        minInput.value = String(min);
        maxInput.value = String(max);
        label.textContent = min + ' a ' + max + ' anos';
    }

    minRange.addEventListener('input', atualizarRangeIdade);
    maxRange.addEventListener('input', atualizarRangeIdade);
    atualizarRangeIdade();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
