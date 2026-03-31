<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php
$estadosCivis = opcoesEstadoCivil();
$ufs = opcoesUF();
?>

<div class="page-header">
    <h1>Cadastro de Pessoas</h1>
    <p class="page-header-subtitulo">Registre os membros com os dados completos, incluindo endereço com preenchimento automático por CEP.</p>
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
            <label for="nome_conjuge">Nome do cônjuge</label>
            <input type="text" id="nome_conjuge" name="nome_conjuge" pattern="^[\p{L}\s]+$" value="<?php echo htmlspecialchars($_POST['nome_conjuge'] ?? ''); ?>">
        </div>
    </div>

    <div class="campo">
        <div class="checkbox-item">
            <input type="checkbox" id="eh_lider" name="eh_lider" value="1" <?php echo isset($_POST['eh_lider']) ? 'checked' : ''; ?>>
            <label for="eh_lider">É líder</label>
        </div>
    </div>

    <div class="grid" id="bloco_lideranca">
        <div class="campo">
            <div class="checkbox-item">
                <input type="checkbox" id="lider_grupo_familiar" name="lider_grupo_familiar" value="1" <?php echo isset($_POST['lider_grupo_familiar']) ? 'checked' : ''; ?>>
                <label for="lider_grupo_familiar">Líder de Grupo Familiar</label>
            </div>
        </div>
        <div class="campo">
            <div class="checkbox-item">
                <input type="checkbox" id="lider_departamento" name="lider_departamento" value="1" <?php echo isset($_POST['lider_departamento']) ? 'checked' : ''; ?>>
                <label for="lider_departamento">Líder de Departamento</label>
            </div>
        </div>
    </div>

    <div class="campo">
        <label for="grupo_familiar_id">Grupo Familiar</label>
        <select id="grupo_familiar_id" name="grupo_familiar_id">
            <option value="">Não vincular agora</option>
            <?php foreach ($gruposFamiliares as $grupo): ?>
                <option value="<?php echo (int) $grupo['id']; ?>" <?php echo ((int) ($_POST['grupo_familiar_id'] ?? 0) === (int) $grupo['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($grupo['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="telefone_fixo">Telefone fixo</label>
            <input type="text" id="telefone_fixo" name="telefone_fixo" inputmode="numeric" maxlength="11" pattern="\d{10,11}" value="<?php echo htmlspecialchars($_POST['telefone_fixo'] ?? ''); ?>">
        </div>
        <div class="campo">
            <label for="telefone_movel">Telefone móvel</label>
            <input type="text" id="telefone_movel" name="telefone_movel" inputmode="numeric" maxlength="11" pattern="\d{11}" value="<?php echo htmlspecialchars($_POST['telefone_movel'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-secao">
        <div class="form-secao-titulo">Endereço</div>
        <div class="grid-endereco-pessoa">
            <div class="campo">
                <label for="endereco_cep">CEP</label>
                <input type="text" id="endereco_cep" name="endereco_cep" required inputmode="numeric" maxlength="8" pattern="\d{8}" value="<?php echo htmlspecialchars($_POST['endereco_cep'] ?? ''); ?>">
            </div>
            <div class="campo campo-endereco-logradouro">
                <label for="endereco_logradouro">Endereço</label>
                <input type="text" id="endereco_logradouro" name="endereco_logradouro" required value="<?php echo htmlspecialchars($_POST['endereco_logradouro'] ?? ''); ?>">
            </div>
            <div class="campo">
                <label for="endereco_numero">Número</label>
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
            <label for="concluiu_integracao">Concluiu integração?</label>
            <select id="concluiu_integracao" name="concluiu_integracao" required>
                <option value="">Selecione</option>
                <option value="1" <?php echo (($_POST['concluiu_integracao'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                <option value="0" <?php echo (($_POST['concluiu_integracao'] ?? '') === '0') ? 'selected' : ''; ?>>Não</option>
            </select>
        </div>
        <div class="campo">
            <label for="participou_retiro_integracao">Participou do retiro de integração?</label>
            <select id="participou_retiro_integracao" name="participou_retiro_integracao" required>
                <option value="">Selecione</option>
                <option value="1" <?php echo (($_POST['participou_retiro_integracao'] ?? '') === '1') ? 'selected' : ''; ?>>Sim</option>
                <option value="0" <?php echo (($_POST['participou_retiro_integracao'] ?? '') === '0') ? 'selected' : ''; ?>>Não</option>
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
    const campoEstadoCivil = document.getElementById('estado_civil');
    const campoNomeConjuge = document.getElementById('campo_nome_conjuge');
    const inputNomeConjuge = document.getElementById('nome_conjuge');
    const checkboxLider = document.getElementById('eh_lider');
    const blocoLideranca = document.getElementById('bloco_lideranca');
    const camposLideranca = blocoLideranca ? blocoLideranca.querySelectorAll('input[type="checkbox"]') : [];
    const campoCep = document.getElementById('endereco_cep');
    const campoLogradouro = document.getElementById('endereco_logradouro');
    const campoBairro = document.getElementById('endereco_bairro');
    const campoCidade = document.getElementById('endereco_cidade');
    const campoUf = document.getElementById('endereco_uf');

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

    if (campoData) campoData.addEventListener('change', atualizarIdade);
    if (campoEstadoCivil) campoEstadoCivil.addEventListener('change', atualizarConjuge);
    if (checkboxLider) checkboxLider.addEventListener('change', atualizarLideranca);
    if (campoCep) campoCep.addEventListener('blur', buscarCep);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
