<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
$estadosCivis = opcoesEstadoCivil();
$generos = opcoesGenero();
$ufs = opcoesUF();
$cargoLabel = (($pessoa['cargo'] ?? '') === 'admin') ? 'Administrador' : 'Membro';
?>

<div class="menu">
    <?php if ($forcarTroca): ?>
        <a href="/logout.php">&larr; Voltar para login</a>
    <?php else: ?>
        <a href="/index.php">&larr; Voltar para inicio</a>
    <?php endif; ?>
</div>

<h1>Meu Perfil</h1>

<?php if ($forcarTroca): ?>
    <div class="erro">
        No primeiro acesso, voce precisa definir uma nova senha antes de continuar.
    </div>
<?php endif; ?>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<div class="card-perfil">
    <h2>Dados cadastrais</h2>

    <form method="POST" action="/meu_perfil.php<?php echo $forcarTroca ? '?forcar_troca=1' : ''; ?>">
        <input type="hidden" name="acao" value="atualizar_perfil">

        <div class="grid">
            <div class="campo">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required pattern="^[\p{L}\s]+$" value="<?php echo htmlspecialchars($pessoa['nome'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" required inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($pessoa['cpf'] ?? ''); ?>">
            </div>
        </div>

        <div class="grid">
            <div class="campo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($pessoa['email'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label>Perfil do sistema</label>
                <input type="text" value="<?php echo htmlspecialchars($cargoLabel); ?>" readonly>
            </div>
        </div>

        <div class="grid">
            <div class="campo">
                <label for="data_nascimento">Data de nascimento</label>
                <input type="date" id="data_nascimento" name="data_nascimento" required value="<?php echo htmlspecialchars($pessoa['data_nascimento'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="idade_exibida">Idade</label>
                <input type="text" id="idade_exibida" readonly value="">
            </div>
        </div>

        <div class="grid">
            <div class="campo">
                <label for="genero">Genero</label>
                <select id="genero" name="genero" required>
                    <option value="">Selecione</option>
                    <?php foreach ($generos as $valor => $label): ?>
                        <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($pessoa['genero'] ?? '') === $valor) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="estado_civil">Estado civil</label>
                <select id="estado_civil" name="estado_civil" required>
                    <?php foreach ($estadosCivis as $valor => $label): ?>
                        <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($pessoa['estado_civil'] ?? '') === $valor) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="bloco_conjuge" class="form-secao" style="display:none;">
            <div class="form-secao-titulo">Conjuge / Companheiro</div>

            <div class="grid">
                <div class="campo">
                    <label for="conjuge_cpf">CPF do conjuge/companheiro</label>
                    <input type="text" id="conjuge_cpf" name="conjuge_cpf" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($pessoa['conjuge_cpf'] ?? ''); ?>">
                </div>
                <div class="campo">
                    <label for="nome_conjuge">Nome do conjuge/companheiro</label>
                    <input type="text" id="nome_conjuge" name="nome_conjuge" pattern="^[\p{L}\s]+$" value="<?php echo htmlspecialchars($pessoa['nome_conjuge'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div id="bloco_responsaveis" class="form-secao" style="display:none;">
            <div class="form-secao-titulo">Responsaveis</div>

            <div class="grid">
                <div class="campo">
                    <label for="responsavel_1_cpf">CPF do responsavel</label>
                    <input type="text" id="responsavel_1_cpf" name="responsavel_1_cpf" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($pessoa['responsavel_1_cpf'] ?? ''); ?>">
                </div>
                <div class="campo">
                    <label for="responsavel_1_nome">Nome do responsavel</label>
                    <input type="text" id="responsavel_1_nome" name="responsavel_1_nome" pattern="^[\p{L}\s]+$" value="<?php echo htmlspecialchars($pessoa['responsavel_1_nome'] ?? ''); ?>">
                </div>
            </div>

            <div class="campo">
                <div class="checkbox-item">
                    <input type="checkbox" id="adicionar_segundo_responsavel" name="adicionar_segundo_responsavel" value="1" <?php echo ((string) ($pessoa['adicionar_segundo_responsavel'] ?? '') === '1' || !empty($pessoa['responsavel_2_cpf']) || !empty($pessoa['responsavel_2_nome'])) ? 'checked' : ''; ?>>
                    <label for="adicionar_segundo_responsavel">Adicionar segundo responsavel</label>
                </div>
            </div>

            <div id="bloco_segundo_responsavel" class="grid" style="display:none;">
                <div class="campo">
                    <label for="responsavel_2_cpf">CPF do segundo responsavel</label>
                    <input type="text" id="responsavel_2_cpf" name="responsavel_2_cpf" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($pessoa['responsavel_2_cpf'] ?? ''); ?>">
                </div>
                <div class="campo">
                    <label for="responsavel_2_nome">Nome do segundo responsavel</label>
                    <input type="text" id="responsavel_2_nome" name="responsavel_2_nome" pattern="^[\p{L}\s]+$" value="<?php echo htmlspecialchars($pessoa['responsavel_2_nome'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="grid">
            <div class="campo">
                <label for="telefone_fixo">Telefone fixo</label>
                <input type="text" id="telefone_fixo" name="telefone_fixo" inputmode="numeric" maxlength="11" pattern="\d{10,11}" value="<?php echo htmlspecialchars($pessoa['telefone_fixo'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="telefone_movel">Telefone movel</label>
                <input type="text" id="telefone_movel" name="telefone_movel" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($pessoa['telefone_movel'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-secao">
            <div class="form-secao-titulo">Endereco</div>
            <div class="grid-endereco-pessoa">
                <div class="campo">
                    <label for="endereco_cep">CEP</label>
                    <input type="text" id="endereco_cep" name="endereco_cep" inputmode="numeric" maxlength="8" pattern="\d{8}" value="<?php echo htmlspecialchars($pessoa['endereco_cep'] ?? ''); ?>">
                </div>
                <div class="campo campo-endereco-logradouro">
                    <label for="endereco_logradouro">Endereco</label>
                    <input type="text" id="endereco_logradouro" name="endereco_logradouro" value="<?php echo htmlspecialchars($pessoa['endereco_logradouro'] ?? ''); ?>">
                </div>
                <div class="campo">
                    <label for="endereco_numero">Numero</label>
                    <input type="text" id="endereco_numero" name="endereco_numero" value="<?php echo htmlspecialchars($pessoa['endereco_numero'] ?? ''); ?>">
                </div>
                <div class="campo campo-endereco-complemento">
                    <label for="endereco_complemento">Complemento</label>
                    <input type="text" id="endereco_complemento" name="endereco_complemento" value="<?php echo htmlspecialchars($pessoa['endereco_complemento'] ?? ''); ?>">
                </div>
                <div class="campo">
                    <label for="endereco_bairro">Bairro</label>
                    <input type="text" id="endereco_bairro" name="endereco_bairro" value="<?php echo htmlspecialchars($pessoa['endereco_bairro'] ?? ''); ?>">
                </div>
                <div class="campo">
                    <label for="endereco_cidade">Cidade</label>
                    <input type="text" id="endereco_cidade" name="endereco_cidade" value="<?php echo htmlspecialchars($pessoa['endereco_cidade'] ?? ''); ?>">
                </div>
                <div class="campo">
                    <label for="endereco_uf">UF</label>
                    <select id="endereco_uf" name="endereco_uf">
                        <option value="">Selecione</option>
                        <?php foreach ($ufs as $uf): ?>
                            <option value="<?php echo htmlspecialchars($uf); ?>" <?php echo (($pessoa['endereco_uf'] ?? '') === $uf) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($uf); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-secao">
            <div class="form-secao-titulo">Campos controlados pela lideranca (somente leitura)</div>

            <div class="grid">
                <div class="campo">
                    <label>Perfil do sistema</label>
                    <input type="text" value="<?php echo htmlspecialchars($cargoLabel); ?>" readonly>
                </div>
                <div class="campo">
                    <label>E lider?</label>
                    <input type="text" value="<?php echo htmlspecialchars(labelSimNao((int) ($pessoa['eh_lider'] ?? 0))); ?>" readonly>
                </div>
            </div>

            <div class="grid">
                <div class="campo">
                    <label>Lider de Grupo Familiar?</label>
                    <input type="text" value="<?php echo htmlspecialchars(labelSimNao((int) ($pessoa['lider_grupo_familiar'] ?? 0))); ?>" readonly>
                </div>
                <div class="campo">
                    <label>Lider de Departamento?</label>
                    <input type="text" value="<?php echo htmlspecialchars(labelSimNao((int) ($pessoa['lider_departamento'] ?? 0))); ?>" readonly>
                </div>
            </div>

            <div class="grid">
                <div class="campo">
                    <label>Grupo Familiar a que pertence</label>
                    <input type="text" value="<?php echo htmlspecialchars((string) ($pessoa['grupo_familiar_nome'] ?? '—')); ?>" readonly>
                </div>
                <div class="campo">
                    <label>Concluiu integracao?</label>
                    <input type="text" value="<?php echo htmlspecialchars(labelSimNao((int) ($pessoa['concluiu_integracao'] ?? 0))); ?>" readonly>
                </div>
            </div>

            <div class="grid">
                <div class="campo">
                    <label>Participou do retiro de integracao?</label>
                    <input type="text" value="<?php echo htmlspecialchars(labelSimNao((int) ($pessoa['participou_retiro_integracao'] ?? 0))); ?>" readonly>
                </div>
            </div>
        </div>

        <button type="submit">Salvar dados</button>
    </form>
</div>

<div
    id="secao-troca-senha"
    class="card-perfil<?php echo $destacarTrocaSenha ? ' card-perfil-destaque' : ''; ?>"
    <?php echo $destacarTrocaSenha ? 'tabindex="-1"' : ''; ?>
>
    <h2>Seguranca da conta</h2>

    <?php if ($destacarTrocaSenha): ?>
        <div class="perfil-alerta-senha">
            <strong>Proximo passo obrigatorio:</strong> defina sua nova senha nesta secao para concluir o primeiro acesso.
        </div>
    <?php endif; ?>

    <?php if (($erroSenha ?? '') !== ''): ?>
        <div class="erro"><?php echo htmlspecialchars($erroSenha); ?></div>
    <?php endif; ?>

    <form method="POST" action="/meu_perfil.php<?php echo $forcarTroca ? '?forcar_troca=1' : ''; ?>">
        <input type="hidden" name="acao" value="alterar_senha">

        <?php if (!$forcarTroca): ?>
            <div class="campo">
                <label for="senha_atual">Senha atual</label>
                <input type="password" id="senha_atual" name="senha_atual" required>
            </div>
        <?php endif; ?>

        <div class="campo">
            <label for="nova_senha">Nova senha</label>
            <input type="password" id="nova_senha" name="nova_senha" required minlength="8" <?php echo $destacarTrocaSenha ? 'autofocus' : ''; ?>>
            <small>Minimo de 8 caracteres, com letra maiuscula, minuscula, numero e simbolo.</small>
        </div>

        <div class="campo">
            <label for="confirmar_senha">Confirmar nova senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="8">
        </div>

        <button type="submit">Alterar senha</button>
    </form>
</div>

<div class="card-perfil">
    <h2>Privacidade e LGPD</h2>

    <div class="privacy-profile-status">
        <?php if ($privacidadeAceitaAtual): ?>
            <div class="mensagem privacy-profile-banner">Seus documentos de privacidade estao em dia.</div>
        <?php else: ?>
            <div class="erro privacy-profile-banner">Seu aceite de privacidade precisa ser atualizado.</div>
        <?php endif; ?>
    </div>

    <div class="grid-perfil">
        <div class="campo">
            <label>Data do aceite</label>
            <input type="text" value="<?php echo htmlspecialchars($privacidadeAceitaEm ?? 'Ainda nao registrado'); ?>" readonly>
        </div>

        <div class="campo">
            <label>Versao aceita</label>
            <input type="text" value="<?php echo htmlspecialchars(trim(($termosVersaoAceita ?? '-') . ' / ' . ($politicaVersaoAceita ?? '-'))); ?>" readonly>
        </div>
    </div>

    <div class="privacy-links">
        <a href="/termos_uso.php" target="_blank" rel="noopener noreferrer" class="botao-link botao-secundario">Ver Termos de Uso</a>
        <a href="/politica_privacidade.php" target="_blank" rel="noopener noreferrer" class="botao-link botao-secundario">Ver Politica de Privacidade</a>
    </div>

    <p class="privacy-profile-help">Para solicitacoes relacionadas a privacidade ou revisao do aceite, entre em contato com <?php echo htmlspecialchars($supportContact !== '' ? $supportContact : 'a administracao responsavel pelo seu cadastro'); ?>.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campoData = document.getElementById('data_nascimento');
    const campoIdade = document.getElementById('idade_exibida');
    const campoEstadoCivil = document.getElementById('estado_civil');
    const blocoConjuge = document.getElementById('bloco_conjuge');
    const inputConjugeCpf = document.getElementById('conjuge_cpf');
    const inputConjugeNome = document.getElementById('nome_conjuge');
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

    function normalizarDigitos(input) {
        if (!input) return;
        input.value = (input.value || '').replace(/\D/g, '');
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
            const resposta = await fetch('/meu_perfil_buscar_pessoa.php?cpf=' + encodeURIComponent(cpf) + '&ignorar_id=<?php echo (int) $pessoa['id']; ?>', {
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
                inputNome.readOnly = true;
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
    atualizarResponsaveis();

    if (campoData) campoData.addEventListener('change', atualizarIdade);
    if (campoEstadoCivil) campoEstadoCivil.addEventListener('change', atualizarConjuge);
    if (checkboxSegundoResponsavel) checkboxSegundoResponsavel.addEventListener('change', atualizarSegundoResponsavel);
    if (campoCep) campoCep.addEventListener('blur', function() { normalizarDigitos(campoCep); buscarCep(); });
    if (document.getElementById('cpf')) document.getElementById('cpf').addEventListener('blur', function() { normalizarDigitos(this); });
    if (inputConjugeCpf) inputConjugeCpf.addEventListener('blur', function() { buscarPessoaPorCpf(inputConjugeCpf, inputConjugeNome); });
    if (responsavel1Cpf) responsavel1Cpf.addEventListener('blur', function() { buscarPessoaPorCpf(responsavel1Cpf, responsavel1Nome, { preencherContatoEndereco: true }); });
    if (responsavel2Cpf) responsavel2Cpf.addEventListener('blur', function() { buscarPessoaPorCpf(responsavel2Cpf, responsavel2Nome); });
    if (document.getElementById('telefone_fixo')) document.getElementById('telefone_fixo').addEventListener('blur', function() { normalizarDigitos(this); });
    if (document.getElementById('telefone_movel')) document.getElementById('telefone_movel').addEventListener('blur', function() { normalizarDigitos(this); });

    if (inputConjugeCpf && inputConjugeCpf.value) buscarPessoaPorCpf(inputConjugeCpf, inputConjugeNome);
    if (responsavel1Cpf && responsavel1Cpf.value) buscarPessoaPorCpf(responsavel1Cpf, responsavel1Nome, { preencherContatoEndereco: true });
    if (responsavel2Cpf && responsavel2Cpf.value) buscarPessoaPorCpf(responsavel2Cpf, responsavel2Nome);

    <?php if ($destacarTrocaSenha): ?>
    const secao = document.getElementById('secao-troca-senha');
    const campoNovaSenha = document.getElementById('nova_senha');

    if (secao) {
        secao.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (campoNovaSenha) {
        window.setTimeout(function () {
            campoNovaSenha.focus({ preventScroll: true });
        }, 250);
    }
    <?php endif; ?>
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

