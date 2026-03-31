<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
$estadosCivis = opcoesEstadoCivil();
$ufs = opcoesUF();
?>

<div class="page-header">
    <h1>Cadastro de Pessoas</h1>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/pessoas.php" id="formPessoa">
    <div class="grid">
        <div class="campo">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" required
                   pattern="^[A-Za-zÃ€-Ã¿\s]+$"
                   title="Digite apenas letras e espaços."
                   value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
            <small>Digite somente letras e espaços.</small>
        </div>
        <div class="campo">
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" required
                   inputmode="numeric" maxlength="11" pattern="\d{11}"
                   title="Digite somente nÃºmeros, sem pontos e traÃ§os."
                   value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>">
            <small>Digite somente números, sem pontos e traços.</small>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            <small>Informe o e-mail que será usado para recuperação de senha.</small>
        </div>
        <div class="campo">
            <label for="cargo">Perfil do sistema</label>
            <select id="cargo" name="cargo" required>
                <option value="">Selecione</option>
                <option value="membro" <?php echo (($_POST['cargo'] ?? '') === 'membro') ? 'selected' : ''; ?>>Membro</option>
                <option value="admin"  <?php echo (($_POST['cargo'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="data_nascimento">Data de nascimento</label>
            <input type="date" id="data_nascimento" name="data_nascimento" required
                   value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>">
        </div>
        <div class="campo">
            <label for="idade_exibida">Idade</label>
            <input type="text" id="idade_exibida" readonly value="">
            <small>Calculada automaticamente em anos.</small>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="estado_civil">Estado civil</label>
            <select id="estado_civil" name="estado_civil" required>
                <option value="">Selecione</option>
                <?php foreach ($estadosCivis as $valor => $label): ?>
                    <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($_POST['estado_civil'] ?? '') === $valor) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo" id="campo_nome_conjuge">
            <label for="nome_conjuge">Nome do parceiro</label>
            <input type="text" id="nome_conjuge" name="nome_conjuge"
                   pattern="^[A-Za-zÃ€-Ã¿\s]+$"
                   title="Digite apenas letras e espaços."
                   value="<?php echo htmlspecialchars($_POST['nome_conjuge'] ?? ''); ?>">
        </div>
    </div>

    <div class="campo">
        <div class="checkbox-item">
            <input type="checkbox" id="eh_lider" name="eh_lider" value="1"
                   <?php echo isset($_POST['eh_lider']) ? 'checked' : ''; ?>>
            <label for="eh_lider">É Líder</label>
        </div>
    </div>

    <div class="grid" id="bloco_lideranca">
        <div class="campo">
            <div class="checkbox-item">
                <input type="checkbox" id="lider_grupo_familiar" name="lider_grupo_familiar" value="1"
                       <?php echo isset($_POST['lider_grupo_familiar']) ? 'checked' : ''; ?>>
                <label for="lider_grupo_familiar">Lí­der de Grupo Familiar</label>
            </div>
        </div>
        <div class="campo">
            <div class="checkbox-item">
                <input type="checkbox" id="lider_departamento" name="lider_departamento" value="1"
                       <?php echo isset($_POST['lider_departamento']) ? 'checked' : ''; ?>>
                <label for="lider_departamento">Lí­der de Departamento</label>
            </div>
        </div>
    </div>

    <div class="campo">
        <label for="grupo_familiar_id">Grupo Familiar que pertence</label>
        <select id="grupo_familiar_id" name="grupo_familiar_id">
            <option value="">Não vincular agora</option>
            <?php foreach ($gruposFamiliares as $grupo): ?>
                <option value="<?php echo (int) $grupo['id']; ?>" <?php echo ((int) ($_POST['grupo_familiar_id'] ?? 0) === (int) $grupo['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($grupo['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small>Opcional. Ao vincular aqui, a pessoa também passa a aparecer no GF.</small>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="telefone_fixo">Contato fixo</label>
            <input type="text" id="telefone_fixo" name="telefone_fixo"
                   inputmode="numeric" maxlength="11" pattern="\d{10,11}"
                   value="<?php echo htmlspecialchars($_POST['telefone_fixo'] ?? ''); ?>">
            <small>Digite somente números com DDD.</small>
        </div>
        <div class="campo">
            <label for="telefone_movel">Contato móvel</label>
            <input type="text" id="telefone_movel" name="telefone_movel"
                   inputmode="numeric" maxlength="11" pattern="\d{11}"
                   value="<?php echo htmlspecialchars($_POST['telefone_movel'] ?? ''); ?>">
            <small>Digite somente números com DDD.</small>
        </div>
    </div>

    <div class="form-secao">
        <div class="form-secao-titulo">Endereço</div>
        <div class="grid-endereco-pessoa">
            <div class="campo">
                <label for="endereco_cep">CEP</label>
                <input type="text" id="endereco_cep" name="endereco_cep" required inputmode="numeric" maxlength="8" pattern="\d{8}"
                       value="<?php echo htmlspecialchars($_POST['endereco_cep'] ?? ''); ?>">
            </div>
            <div class="campo campo-endereco-logradouro">
                <label for="endereco_logradouro">Endereço</label>
                <input type="text" id="endereco_logradouro" name="endereco_logradouro" required
                       value="<?php echo htmlspecialchars($_POST['endereco_logradouro'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="endereco_numero">Número</label>
                <input type="text" id="endereco_numero" name="endereco_numero" required
                       value="<?php echo htmlspecialchars($_POST['endereco_numero'] ?? ''); ?>">
            </div>
            <div class="campo campo-endereco-complemento">
                <label for="endereco_complemento">Complemento</label>
                <input type="text" id="endereco_complemento" name="endereco_complemento"
                       value="<?php echo htmlspecialchars($_POST['endereco_complemento'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="endereco_bairro">Bairro</label>
                <input type="text" id="endereco_bairro" name="endereco_bairro" required
                       value="<?php echo htmlspecialchars($_POST['endereco_bairro'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="endereco_cidade">Cidade</label>
                <input type="text" id="endereco_cidade" name="endereco_cidade" required
                       value="<?php echo htmlspecialchars($_POST['endereco_cidade'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="endereco_uf">UF</label>
                <select id="endereco_uf" name="endereco_uf" required>
                    <option value="">Selecione</option>
                    <?php foreach ($ufs as $uf): ?>
                        <option value="<?php echo htmlspecialchars($uf); ?>" <?php echo (($_POST['endereco_uf'] ?? '') === $uf) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($uf); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="concluiu_integracao">Concluiu integração?</label>
            <select id="concluiu_integracao" name="concluiu_integracao" required>
                <option value="">Selecione</option>
                <option value="1" <?php echo (($_POST['concluiu_integracao'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                <option value="0" <?php echo (($_POST['concluiu_integracao'] ?? '') === '0') ? 'selected' : ''; ?>>Não</option>
            </select>
        </div>
        <div class="campo">
            <label for="participou_retiro_integracao">Já participou do retiro de integração?</label>
            <select id="participou_retiro_integracao" name="participou_retiro_integracao" required>
                <option value="">Selecione</option>
                <option value="1" <?php echo (($_POST['participou_retiro_integracao'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                <option value="0" <?php echo (($_POST['participou_retiro_integracao'] ?? '') === '0') ? 'selected' : ''; ?>>Não</option>
            </select>
        </div>
    </div>

    <button type="submit">Cadastrar pessoa</button>
</form>

<h2 style="margin-top:32px; margin-bottom:16px;">Pessoas cadastradas</h2>

<form method="GET" action="/pessoas.php" id="filtrosPessoasTabela"></form>
<div class="tabela-wrapper tabela-cadastro-completa">
    <table class="tabela-pessoas tabela-cadastro-grid">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Data Nasc.</th>
                <th>Contato</th>
                <th>Endereço</th>
                <th>Estado Civil</th>
                <th>Liderança</th>
                <th>GF</th>
                <th>Integração</th>
                <th>Retiro</th>
                <th>Status</th>
                <th class="tabela-acoes">Ações</th>
            </tr>
            <tr class="filtros-linha">
                <th><input class="tabela-filtro-campo tabela-filtro-input-curto" form="filtrosPessoasTabela" type="text" name="id" value="<?php echo htmlspecialchars($filtros['id'] ?? ''); ?>" placeholder="ID"></th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="nome" value="<?php echo htmlspecialchars($filtros['nome'] ?? ''); ?>" placeholder="Nome"></th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="cpf" value="<?php echo htmlspecialchars($filtros['cpf'] ?? ''); ?>" placeholder="CPF"></th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="contato" value="<?php echo htmlspecialchars($filtros['contato'] ?? ''); ?>" placeholder="E-mail"></th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="cargo">
                        <option value="">Todos</option>
                        <option value="membro" <?php echo (($filtros['cargo'] ?? '') === 'membro') ? 'selected' : ''; ?>>Membro</option>
                        <option value="admin" <?php echo (($filtros['cargo'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="date" name="data_nascimento" value="<?php echo htmlspecialchars($filtros['data_nascimento'] ?? ''); ?>"></th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="telefone" value="<?php echo htmlspecialchars($filtros['telefone'] ?? ''); ?>" placeholder="Fixo ou móvel"></th>
                <th><input class="tabela-filtro-campo" form="filtrosPessoasTabela" type="text" name="endereco" value="<?php echo htmlspecialchars($filtros['endereco'] ?? ''); ?>" placeholder="Rua, bairro, cidade..."></th>
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
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="lideranca">
                        <option value="">Todos</option>
                        <option value="nao" <?php echo (($filtros['lideranca'] ?? '') === 'nao') ? 'selected' : ''; ?>>Sem liderança</option>
                        <option value="gf" <?php echo (($filtros['lideranca'] ?? '') === 'gf') ? 'selected' : ''; ?>>GF</option>
                        <option value="dpto" <?php echo (($filtros['lideranca'] ?? '') === 'dpto') ? 'selected' : ''; ?>>Dpto.</option>
                        <option value="gf_e_dpto" <?php echo (($filtros['lideranca'] ?? '') === 'gf_e_dpto') ? 'selected' : ''; ?>>GF e Dpto.</option>
                        <option value="gf_ou_dpto" <?php echo (($filtros['lideranca'] ?? '') === 'gf_ou_dpto') ? 'selected' : ''; ?>>GF e/ou Dpto.</option>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="grupo_familiar_id">
                        <option value="">Todos</option>
                        <?php foreach ($gruposFamiliares as $grupo): ?>
                            <option value="<?php echo (int) $grupo['id']; ?>" <?php echo ((int) ($filtros['grupo_familiar_id'] ?? 0) === (int) $grupo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grupo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="concluiu_integracao">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($filtros['concluiu_integracao'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?php echo (($filtros['concluiu_integracao'] ?? '') === '0') ? 'selected' : ''; ?>>Não</option>
                    </select>
                </th>
                <th>
                    <select class="tabela-filtro-campo" form="filtrosPessoasTabela" name="participou_retiro_integracao">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (($filtros['participou_retiro_integracao'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?php echo (($filtros['participou_retiro_integracao'] ?? '') === '0') ? 'selected' : ''; ?>>Não</option>
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
                        <a class="botao-link botao-secundario" href="/pessoas.php">Limpar</a>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($pessoas) === 0): ?>
                <tr>
                    <td colspan="15" class="tabela-vazia">Nenhuma pessoa encontrada para o filtro atual.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($pessoas as $registro): ?>
                <?php
                $ativo = (int) $registro['ativo'] === 1;
                $idade = calcularIdade($registro['data_nascimento'] ?? null);
                $liderancas = [];
                if ((int) ($registro['lider_grupo_familiar'] ?? 0) === 1) {
                    $liderancas[] = 'GF';
                }
                if ((int) ($registro['lider_departamento'] ?? 0) === 1) {
                    $liderancas[] = 'Dpto.';
                }
                $liderancaTexto = count($liderancas) > 0 ? implode(' / ', $liderancas) : '-';
                $motivoDesativacao = labelMotivoDesativacaoPessoa($registro['motivo_desativacao_tipo'] ?? null);
                if (!empty($registro['motivo_desativacao_detalhe'])) {
                    $motivoDesativacao .= ': ' . $registro['motivo_desativacao_detalhe'];
                }
                if (!empty($registro['motivo_desativacao_texto'])) {
                    $motivoDesativacao .= ' - ' . $registro['motivo_desativacao_texto'];
                }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($registro['id']); ?></td>
                    <td><div class="tabela-coluna-principal"><?php echo htmlspecialchars($registro['nome']); ?></div></td>
                    <td><?php echo htmlspecialchars($registro['cpf']); ?></td>
                    <td><?php echo htmlspecialchars($registro['email'] ?: '—'); ?></td>
                    <td><span class="badge badge-blue"><?php echo htmlspecialchars(ucfirst((string) $registro['cargo'])); ?></span></td>
                    <td>
                        <div><?php echo htmlspecialchars(formatarDataBr($registro['data_nascimento'] ?? null)); ?></div>
                        <?php if ($idade !== null): ?>
                            <div class="tabela-meta"><?php echo $idade; ?> anos</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?php echo htmlspecialchars(formatarTelefone($registro['telefone_fixo'] ?? null)); ?></div>
                        <div class="tabela-meta"><?php echo htmlspecialchars(formatarTelefone($registro['telefone_movel'] ?? null)); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars(formatarEnderecoPessoa($registro)); ?></td>
                    <td>
                        <div><?php echo htmlspecialchars(labelEstadoCivil($registro['estado_civil'] ?? null)); ?></div>
                        <?php if (!empty($registro['nome_conjuge'])): ?>
                            <div class="tabela-meta"><?php echo htmlspecialchars($registro['nome_conjuge']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($liderancaTexto); ?></td>
                    <td><?php echo htmlspecialchars($registro['grupo_familiar_nome'] ?: '—'); ?></td>
                    <td>
                        <span class="badge <?php echo ((int) ($registro['concluiu_integracao'] ?? 0) === 1) ? 'badge-green' : 'badge-amber'; ?>">
                            <?php echo htmlspecialchars(labelSimNao((int) ($registro['concluiu_integracao'] ?? 0))); ?>
                        </span>
                        <?php if ((int) ($registro['integracao_conclusao_manual'] ?? 0) === 1 && (int) ($registro['concluiu_integracao'] ?? 0) === 1): ?>
                            <div class="tabela-meta">Conclusão manual</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo ((int) ($registro['participou_retiro_integracao'] ?? 0) === 1) ? 'badge-blue' : 'badge-red'; ?>">
                            <?php echo htmlspecialchars(labelSimNao((int) ($registro['participou_retiro_integracao'] ?? 0))); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($ativo): ?>
                            <span class="status-ativo">Ativo</span>
                        <?php else: ?>
                            <span class="status-inativo">Desativado</span>
                            <?php if ($motivoDesativacao !== '-'): ?>
                                <div class="notif-motivo"><?php echo htmlspecialchars($motivoDesativacao); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="tabela-acoes">
                        <div class="acoes" style="flex-direction:column; gap:6px; align-items:stretch;">
                            <a class="btn-gf btn-gf-editar" href="/pessoas_editar.php?id=<?php echo $registro['id']; ?>">Editar</a>
                            <a class="btn-gf btn-gf-integracao" href="/pessoas_integracao.php?id=<?php echo $registro['id']; ?>">Aulas Integração</a>
                            <?php if ($ativo): ?>
                                <a class="btn-gf btn-gf-desativar" href="/pessoas_desativar.php?id=<?php echo $registro['id']; ?>">Desativar</a>
                            <?php else: ?>
                                <form method="POST" action="/pessoas_reativar.php" class="form-acao"
                                      onsubmit="return confirm('Deseja realmente reativar esta pessoa?');">
                                    <input type="hidden" name="id" value="<?php echo $registro['id']; ?>">
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
    const campoData = document.getElementById('data_nascimento');
    const campoIdade = document.getElementById('idade_exibida');
    const campoEstadoCivil = document.getElementById('estado_civil');
    const campoNomeConjuge = document.getElementById('campo_nome_conjuge');
    const inputNomeConjuge = document.getElementById('nome_conjuge');
    const checkboxLider = document.getElementById('eh_lider');
    const blocoLideranca = document.getElementById('bloco_lideranca');
    const camposLideranca = blocoLideranca ? blocoLideranca.querySelectorAll('input[type="checkbox"]') : [];

    function atualizarIdade() {
        if (!campoData || !campoIdade || !campoData.value) {
            if (campoIdade) campoIdade.value = '';
            return;
        }
        const hoje = new Date();
        const nascimento = new Date(campoData.value + 'T00:00:00');
        let idade = hoje.getFullYear() - nascimento.getFullYear();
        const mes = hoje.getMonth() - nascimento.getMonth();
        if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
            idade--;
        }
        campoIdade.value = Number.isNaN(idade) ? '' : idade + ' anos';
    }

    function atualizarConjuge() {
        if (!campoEstadoCivil || !campoNomeConjuge || !inputNomeConjuge) return;
        const precisa = ['casado', 'uniao_estavel'].includes(campoEstadoCivil.value);
        campoNomeConjuge.style.display = precisa ? '' : 'none';
        inputNomeConjuge.required = precisa;
        if (!precisa) inputNomeConjuge.value = '';
    }

    function atualizarLideranca() {
        if (!checkboxLider || !blocoLideranca) return;
        blocoLideranca.style.display = checkboxLider.checked ? '' : 'none';
        if (!checkboxLider.checked) {
            camposLideranca.forEach(function(campo) {
                campo.checked = false;
            });
        }
    }

    atualizarIdade();
    atualizarConjuge();
    atualizarLideranca();

    if (campoData) campoData.addEventListener('change', atualizarIdade);
    if (campoEstadoCivil) campoEstadoCivil.addEventListener('change', atualizarConjuge);
    if (checkboxLider) checkboxLider.addEventListener('change', atualizarLideranca);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
