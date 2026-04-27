<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
$estadosCivis = opcoesEstadoCivil();
$generos = opcoesGenero();
$ufs = opcoesUF();
?>

<div class="page-header">
    <h1>Cadastro de Pessoas</h1>
    <p class="page-header-subtitulo">Registre os membros com os dados completos, incluindo endere&ccedil;o com preenchimento autom&aacute;tico por CEP.</p>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/pessoas.php">
    <div class="grid">
        <div class="campo">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" required pattern="^[\p{L}\s]+$" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
        </div>
        <div class="campo">
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" required inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>">
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="campo">
            <label for="cargo">Perfil do sistema</label>
            <select id="cargo" name="cargo" required>
                <option value="">Selecione</option>
                <option value="membro" <?php echo (($_POST['cargo'] ?? '') === 'membro') ? 'selected' : ''; ?>>Membro</option>
                <option value="admin" <?php echo (($_POST['cargo'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrador</option>
            </select>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="data_nascimento">Data de nascimento</label>
            <input type="date" id="data_nascimento" name="data_nascimento" required value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>">
        </div>
        <div class="campo">
            <label for="idade_exibida">Idade</label>
            <input type="text" id="idade_exibida" readonly value="">
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="genero">G&ecirc;nero</label>
            <select id="genero" name="genero" required>
                <option value="">Selecione</option>
                <?php foreach ($generos as $valor => $label): ?>
                    <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($_POST['genero'] ?? '') === $valor) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
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
    </div>

    <div id="bloco_conjuge" class="form-secao" style="display:none;">
        <div class="form-secao-titulo">C&ocirc;njuge / Companheiro</div>

        <div class="grid">
            <div class="campo">
                <label for="conjuge_cpf">CPF do c&ocirc;njuge/companheiro</label>
                <input type="text" id="conjuge_cpf" name="conjuge_cpf" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($_POST['conjuge_cpf'] ?? ''); ?>">
                <small>Se j&aacute; estiver cadastrado, o nome ser&aacute; preenchido automaticamente.</small>
            </div>
            <div class="campo">
                <label for="nome_conjuge">Nome do c&ocirc;njuge/companheiro</label>
                <input type="text" id="nome_conjuge" name="nome_conjuge" list="nome_conjuge_lista" pattern="^[\p{L}\s]+$" value="<?php echo htmlspecialchars($_POST['nome_conjuge'] ?? ''); ?>">
                <datalist id="nome_conjuge_lista"></datalist>
            </div>
        </div>
    </div>

    <div id="bloco_responsaveis" class="form-secao" style="display:none;">
        <div class="form-secao-titulo">Respons&aacute;veis</div>

        <div class="grid">
            <div class="campo">
                <label for="responsavel_1_cpf">CPF do respons&aacute;vel</label>
                <input type="text" id="responsavel_1_cpf" name="responsavel_1_cpf" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($_POST['responsavel_1_cpf'] ?? ''); ?>">
                <small>Se o respons&aacute;vel j&aacute; estiver cadastrado, o nome ser&aacute; preenchido automaticamente. Para menores de 18 anos, e-mail, contatos e endere&ccedil;o tamb&eacute;m podem ser importados e continuar&atilde;o edit&aacute;veis.</small>
            </div>
            <div class="campo">
                <label for="responsavel_1_nome">Nome do respons&aacute;vel</label>
                <input type="text" id="responsavel_1_nome" name="responsavel_1_nome" list="responsavel_1_nome_lista" pattern="^[\p{L}\s]+$" value="<?php echo htmlspecialchars($_POST['responsavel_1_nome'] ?? ''); ?>">
                <datalist id="responsavel_1_nome_lista"></datalist>
            </div>
        </div>

        <div class="campo">
            <div class="checkbox-item">
                <input type="checkbox" id="adicionar_segundo_responsavel" name="adicionar_segundo_responsavel" value="1" <?php echo (($_POST['adicionar_segundo_responsavel'] ?? '') === '1') ? 'checked' : ''; ?>>
                <label for="adicionar_segundo_responsavel">Adicionar segundo respons&aacute;vel</label>
            </div>
        </div>

        <div id="bloco_segundo_responsavel" class="grid" style="display:none;">
            <div class="campo">
                <label for="responsavel_2_cpf">CPF do segundo respons&aacute;vel</label>
                <input type="text" id="responsavel_2_cpf" name="responsavel_2_cpf" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($_POST['responsavel_2_cpf'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="responsavel_2_nome">Nome do segundo respons&aacute;vel</label>
                <input type="text" id="responsavel_2_nome" name="responsavel_2_nome" list="responsavel_2_nome_lista" pattern="^[\p{L}\s]+$" value="<?php echo htmlspecialchars($_POST['responsavel_2_nome'] ?? ''); ?>">
                <datalist id="responsavel_2_nome_lista"></datalist>
            </div>
        </div>
    </div>

    <div class="campo">
        <div class="checkbox-item">
            <input type="checkbox" id="eh_lider" name="eh_lider" value="1" <?php echo isset($_POST['eh_lider']) ? 'checked' : ''; ?>>
            <label for="eh_lider">&Eacute; l&iacute;der</label>
        </div>
    </div>

    <div class="grid" id="bloco_lideranca">
        <div class="campo">
            <div class="checkbox-item">
                <input type="checkbox" id="lider_grupo_familiar" name="lider_grupo_familiar" value="1" <?php echo isset($_POST['lider_grupo_familiar']) ? 'checked' : ''; ?>>
                <label for="lider_grupo_familiar">L&iacute;der de Grupo Familiar</label>
            </div>
        </div>
        <div class="campo">
            <div class="checkbox-item">
                <input type="checkbox" id="lider_departamento" name="lider_departamento" value="1" <?php echo isset($_POST['lider_departamento']) ? 'checked' : ''; ?>>
                <label for="lider_departamento">L&iacute;der de Departamento</label>
            </div>
        </div>
    </div>

    <div class="campo">
        <label for="grupo_familiar_id">Grupo Familiar</label>
        <select id="grupo_familiar_id" name="grupo_familiar_id">
            <option value="">N&atilde;o vincular agora</option>
            <?php foreach ($gruposFamiliares as $grupo): ?>
                <option
                    value="<?php echo (int) $grupo['id']; ?>"
                    data-perfil="<?php echo htmlspecialchars($grupo['perfil_grupo'] ?? ''); ?>"
                    <?php echo ((int) ($_POST['grupo_familiar_id'] ?? 0) === (int) $grupo['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($grupo['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small id="grupo_genero_aviso" style="display:none;">Grupos com Perfil de Grupo = Mulheres aceitam apenas pessoas com G&ecirc;nero = Feminino.</small>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="telefone_fixo">Telefone fixo</label>
            <input type="text" id="telefone_fixo" name="telefone_fixo" inputmode="numeric" maxlength="11" pattern="\d{10,11}" value="<?php echo htmlspecialchars($_POST['telefone_fixo'] ?? ''); ?>">
        </div>
        <div class="campo">
            <label for="telefone_movel">Telefone m&oacute;vel</label>
            <input type="text" id="telefone_movel" name="telefone_movel" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($_POST['telefone_movel'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-secao">
        <div class="form-secao-titulo">Endere&ccedil;o</div>
        <div class="grid-endereco-pessoa">
            <div class="campo">
                <label for="endereco_cep">CEP</label>
                <input type="text" id="endereco_cep" name="endereco_cep" required inputmode="numeric" maxlength="8" pattern="\d{8}" value="<?php echo htmlspecialchars($_POST['endereco_cep'] ?? ''); ?>">
            </div>
            <div class="campo campo-endereco-logradouro">
                <label for="endereco_logradouro">Endere&ccedil;o</label>
                <input type="text" id="endereco_logradouro" name="endereco_logradouro" required value="<?php echo htmlspecialchars($_POST['endereco_logradouro'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="endereco_numero">N&uacute;mero</label>
                <input type="text" id="endereco_numero" name="endereco_numero" required value="<?php echo htmlspecialchars($_POST['endereco_numero'] ?? ''); ?>">
            </div>
            <div class="campo campo-endereco-complemento">
                <label for="endereco_complemento">Complemento</label>
                <input type="text" id="endereco_complemento" name="endereco_complemento" value="<?php echo htmlspecialchars($_POST['endereco_complemento'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="endereco_bairro">Bairro</label>
                <input type="text" id="endereco_bairro" name="endereco_bairro" required value="<?php echo htmlspecialchars($_POST['endereco_bairro'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="endereco_cidade">Cidade</label>
                <input type="text" id="endereco_cidade" name="endereco_cidade" required value="<?php echo htmlspecialchars($_POST['endereco_cidade'] ?? ''); ?>">
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
            <label for="concluiu_integracao">Concluiu integra&ccedil;&atilde;o?</label>
            <select id="concluiu_integracao" name="concluiu_integracao" required>
                <option value="">Selecione</option>
                <option value="1" <?php echo (($_POST['concluiu_integracao'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                <option value="0" <?php echo (($_POST['concluiu_integracao'] ?? '') === '0') ? 'selected' : ''; ?>>N&atilde;o</option>
            </select>
        </div>
        <div class="campo">
            <label for="participou_retiro_integracao">Participou do retiro de integra&ccedil;&atilde;o?</label>
            <select id="participou_retiro_integracao" name="participou_retiro_integracao" required>
                <option value="">Selecione</option>
                <option value="1" <?php echo (($_POST['participou_retiro_integracao'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                <option value="0" <?php echo (($_POST['participou_retiro_integracao'] ?? '') === '0') ? 'selected' : ''; ?>>N&atilde;o</option>
            </select>
        </div>
    </div>

    <div class="acoes">
        <button type="submit">Cadastrar pessoa</button>
        <a href="/pessoas_cadastradas.php" class="botao-link botao-secundario">Ver pessoas cadastradas</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campoData = document.getElementById('data_nascimento');
    const campoIdade = document.getElementById('idade_exibida');
    const campoGenero = document.getElementById('genero');
    const campoEstadoCivil = document.getElementById('estado_civil');
    const blocoConjuge = document.getElementById('bloco_conjuge');
    const inputConjugeCpf = document.getElementById('conjuge_cpf');
    const inputConjugeNome = document.getElementById('nome_conjuge');
    const checkboxLider = document.getElementById('eh_lider');
    const blocoLideranca = document.getElementById('bloco_lideranca');
    const camposLideranca = blocoLideranca ? blocoLideranca.querySelectorAll('input[type="checkbox"]') : [];
    const campoGrupoFamiliar = document.getElementById('grupo_familiar_id');
    const avisoGrupoGenero = document.getElementById('grupo_genero_aviso');
    const campoCep = document.getElementById('endereco_cep');
    const campoLogradouro = document.getElementById('endereco_logradouro');
    const campoBairro = document.getElementById('endereco_bairro');
    const campoCidade = document.getElementById('endereco_cidade');
    const campoUf = document.getElementById('endereco_uf');
    const blocoResponsaveis = document.getElementById('bloco_responsaveis');
    const checkboxSegundoResponsavel = document.getElementById('adicionar_segundo_responsavel');
    const blocoSegundoResponsavel = document.getElementById('bloco_segundo_responsavel');
    const responsavel1Cpf = document.getElementById('responsavel_1_cpf');
    const responsavel1Nome = document.getElementById('responsavel_1_nome');
    const responsavel2Cpf = document.getElementById('responsavel_2_cpf');
    const responsavel2Nome = document.getElementById('responsavel_2_nome');
    const camposAutofillResponsavel = {
        email: document.getElementById('email'),
        telefone_fixo: document.getElementById('telefone_fixo'),
        telefone_movel: document.getElementById('telefone_movel'),
        endereco_cep: document.getElementById('endereco_cep'),
        endereco_logradouro: document.getElementById('endereco_logradouro'),
        endereco_numero: document.getElementById('endereco_numero'),
        endereco_complemento: document.getElementById('endereco_complemento'),
        endereco_bairro: document.getElementById('endereco_bairro'),
        endereco_cidade: document.getElementById('endereco_cidade'),
        endereco_uf: document.getElementById('endereco_uf'),
    };

    function idadeAtual() {
        if (!campoData || !campoData.value) return null;
        const hoje = new Date();
        const nascimento = new Date(campoData.value + 'T00:00:00');
        let idade = hoje.getFullYear() - nascimento.getFullYear();
        const mes = hoje.getMonth() - nascimento.getMonth();
        if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
            idade--;
        }

        return Number.isNaN(idade) ? null : idade;
    }

    function atualizarIdade() {
        if (!campoData || !campoIdade || !campoData.value) {
            if (campoIdade) campoIdade.value = '';
            atualizarResponsaveis();
            return;
        }

        const idade = idadeAtual();
        campoIdade.value = idade === null ? '' : idade + ' anos';
        atualizarResponsaveis();
    }

    function atualizarConjuge() {
        if (!campoEstadoCivil || !blocoConjuge || !inputConjugeCpf || !inputConjugeNome) return;
        const precisa = ['casado', 'uniao_estavel'].includes(campoEstadoCivil.value);
        blocoConjuge.style.display = precisa ? '' : 'none';
        inputConjugeCpf.required = precisa;
        inputConjugeNome.required = precisa;
        if (!precisa) {
            inputConjugeCpf.value = '';
            inputConjugeNome.value = '';
            inputConjugeNome.readOnly = false;
        }
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

    function atualizarSegundoResponsavel() {
        if (!checkboxSegundoResponsavel || !blocoSegundoResponsavel || !responsavel2Cpf || !responsavel2Nome) return;
        const ativo = checkboxSegundoResponsavel.checked;
        blocoSegundoResponsavel.style.display = ativo ? '' : 'none';
        responsavel2Cpf.required = ativo;
        responsavel2Nome.required = ativo;
        if (!ativo) {
            responsavel2Cpf.value = '';
            responsavel2Nome.value = '';
            responsavel2Nome.readOnly = false;
        }
    }

    function aplicarAutofillCampo(campo, valor) {
        if (!campo) return;

        const proximoValor = (valor || '').trim();
        if (proximoValor === '') return;

        const valorAtual = (campo.value || '').trim();
        const ultimoAutofill = (campo.dataset.autofillValue || '').trim();

        if (valorAtual === '' || valorAtual === ultimoAutofill) {
            campo.value = proximoValor;
            campo.dataset.autofillValue = proximoValor;
        }
    }

    function aplicarAutofillResponsavel(dados) {
        if (idadeAtual() === null || idadeAtual() >= 18) {
            return;
        }

        Object.entries(camposAutofillResponsavel).forEach(function(entry) {
            const [campo, elemento] = entry;
            aplicarAutofillCampo(elemento, dados[campo] || '');
        });
    }

    function atualizarResponsaveis() {
        if (!blocoResponsaveis || !responsavel1Cpf || !responsavel1Nome) return;
        const idade = idadeAtual();
        const menorDeIdade = idade !== null && idade < 18;

        blocoResponsaveis.style.display = menorDeIdade ? '' : 'none';
        responsavel1Cpf.required = menorDeIdade;
        responsavel1Nome.required = menorDeIdade;

        if (!menorDeIdade) {
            responsavel1Cpf.value = '';
            responsavel1Nome.value = '';
            responsavel1Nome.readOnly = false;
            if (checkboxSegundoResponsavel) checkboxSegundoResponsavel.checked = false;
        }

        atualizarSegundoResponsavel();
    }

    function atualizarGrupoPorGenero() {
        if (!campoGrupoFamiliar || !campoGenero) return;
        const genero = campoGenero.value;
        let selecionadoInvalido = false;

        Array.from(campoGrupoFamiliar.options).forEach(function(option) {
            if (!option.value) return;
            const perfil = option.dataset.perfil || '';
            const permitido = perfil !== 'mulheres' || genero === 'feminino';
            option.disabled = !permitido;
            option.hidden = !permitido;
            if (!permitido && option.selected) {
                selecionadoInvalido = true;
            }
        });

        if (selecionadoInvalido) {
            campoGrupoFamiliar.value = '';
        }

        if (avisoGrupoGenero) {
            avisoGrupoGenero.style.display = genero !== 'feminino' ? '' : 'none';
        }
    }

    function montarItemBuscaNome(item) {
        return (item.nome || '').trim();
    }

    function limparOpcoesDatalist(datalist) {
        if (!datalist) return;
        while (datalist.firstChild) {
            datalist.removeChild(datalist.firstChild);
        }
    }

    function configurarBuscaPorNome(inputBusca, inputCpf, inputNome, opcoes = {}) {
        if (!inputBusca || !inputCpf || !inputNome) return;
        const datalistId = inputBusca.getAttribute('list');
        const datalist = datalistId ? document.getElementById(datalistId) : null;
        if (!datalist) return;

        let debounceTimer = null;
        let ultimoResultado = [];

        async function buscarSugestoes() {
            const termo = (inputBusca.value || '').trim();
            if (termo.length < 2) {
                ultimoResultado = [];
                limparOpcoesDatalist(datalist);
                return;
            }

            try {
                const resposta = await fetch('/pessoas_responsavel_buscar.php?nome=' + encodeURIComponent(termo), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!resposta.ok) return;

                const dados = await resposta.json();
                const resultados = Array.isArray(dados.resultados) ? dados.resultados : [];
                ultimoResultado = resultados;
                limparOpcoesDatalist(datalist);

                resultados.forEach(function(item) {
                    const option = document.createElement('option');
                    option.value = montarItemBuscaNome(item);
                    datalist.appendChild(option);
                });
            } catch (erro) {
            }
        }

        function aplicarSelecao() {
            const valorBusca = (inputBusca.value || '').trim();
            if (valorBusca === '') return;

            const candidatos = ultimoResultado.filter(function(item) {
                return montarItemBuscaNome(item).toLowerCase() === valorBusca.toLowerCase();
            });

            const itemSelecionado = candidatos.length > 0 ? candidatos[0] : null;
            const cpfSelecionado = itemSelecionado
                ? String(itemSelecionado.cpf || '').replace(/\D/g, '')
                : '';

            if (cpfSelecionado.length !== 11) return;

            inputCpf.value = cpfSelecionado;
            buscarPessoaPorCpf(inputCpf, inputNome, opcoes);
        }

        inputBusca.addEventListener('input', function() {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(buscarSugestoes, 220);
        });
        inputBusca.addEventListener('change', aplicarSelecao);
        inputBusca.addEventListener('blur', aplicarSelecao);
    }

    async function buscarPessoaPorCpf(inputCpf, inputNome, opcoes = {}) {
        if (!inputCpf || !inputNome) return;
        const cpf = (inputCpf.value || '').replace(/\D/g, '');

        inputCpf.value = cpf;

        if (cpf.length !== 11) {
            inputNome.readOnly = false;
            return;
        }

        try {
            const resposta = await fetch('/pessoas_responsavel_buscar.php?cpf=' + encodeURIComponent(cpf), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!resposta.ok) {
                inputNome.readOnly = false;
                return;
            }

            const dados = await resposta.json();
            if (dados.encontrado && dados.nome) {
                inputNome.value = dados.nome;
                inputNome.readOnly = false;
                if (opcoes.preencherContatoEndereco) {
                    aplicarAutofillResponsavel(dados);
                }
            } else {
                inputNome.readOnly = false;
            }
        } catch (erro) {
            inputNome.readOnly = false;
        }
    }

    async function buscarCep() {
        if (!campoCep) return;
        const cep = (campoCep.value || '').replace(/\D/g, '');
        if (cep.length !== 8) return;

        try {
            const resposta = await fetch('https://viacep.com.br/ws/' + cep + '/json/');
            const dados = await resposta.json();
            if (dados.erro) return;
            if (campoLogradouro && dados.logradouro) campoLogradouro.value = dados.logradouro;
            if (campoBairro && dados.bairro) campoBairro.value = dados.bairro;
            if (campoCidade && dados.localidade) campoCidade.value = dados.localidade;
            if (campoUf && dados.uf) campoUf.value = dados.uf;
        } catch (erro) {
        }
    }

    atualizarIdade();
    atualizarConjuge();
    atualizarLideranca();
    atualizarResponsaveis();
    atualizarGrupoPorGenero();

    if (campoData) campoData.addEventListener('change', atualizarIdade);
    if (campoEstadoCivil) campoEstadoCivil.addEventListener('change', atualizarConjuge);
    if (campoGenero) campoGenero.addEventListener('change', atualizarGrupoPorGenero);
    if (checkboxLider) checkboxLider.addEventListener('change', atualizarLideranca);
    if (campoCep) campoCep.addEventListener('blur', buscarCep);
    if (checkboxSegundoResponsavel) checkboxSegundoResponsavel.addEventListener('change', atualizarSegundoResponsavel);
    if (inputConjugeCpf) inputConjugeCpf.addEventListener('blur', function() { buscarPessoaPorCpf(inputConjugeCpf, inputConjugeNome); });
    if (responsavel1Cpf) responsavel1Cpf.addEventListener('blur', function() { buscarPessoaPorCpf(responsavel1Cpf, responsavel1Nome, { preencherContatoEndereco: true }); });
    if (responsavel2Cpf) responsavel2Cpf.addEventListener('blur', function() { buscarPessoaPorCpf(responsavel2Cpf, responsavel2Nome); });

    configurarBuscaPorNome(inputConjugeNome, inputConjugeCpf, inputConjugeNome);
    configurarBuscaPorNome(responsavel1Nome, responsavel1Cpf, responsavel1Nome, { preencherContatoEndereco: true });
    configurarBuscaPorNome(responsavel2Nome, responsavel2Cpf, responsavel2Nome);

    if (inputConjugeCpf && inputConjugeCpf.value) buscarPessoaPorCpf(inputConjugeCpf, inputConjugeNome);
    if (responsavel1Cpf && responsavel1Cpf.value) buscarPessoaPorCpf(responsavel1Cpf, responsavel1Nome, { preencherContatoEndereco: true });
    if (responsavel2Cpf && responsavel2Cpf.value) buscarPessoaPorCpf(responsavel2Cpf, responsavel2Nome);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
