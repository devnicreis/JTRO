<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../helpers.php'; ?>

<?php $estadosCivis = opcoesEstadoCivil(); ?>

<div class="menu">
    <a href="/pessoas.php">← Voltar para Pessoas</a>
</div>

<h1>Editar Pessoa</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/pessoas_editar.php">
    <input type="hidden" name="id" value="<?php echo (int) $pessoa['id']; ?>">

    <div class="grid">
        <div class="campo">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" required
                   pattern="^[A-Za-zÀ-ÿ\s]+$"
                   title="Digite apenas letras e espaços."
                   value="<?php echo htmlspecialchars($pessoa['nome']); ?>">
        </div>
        <div class="campo">
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" required
                   inputmode="numeric" maxlength="11" pattern="\d{11}"
                   title="Digite somente números, sem pontos e traços."
                   value="<?php echo htmlspecialchars($pessoa['cpf']); ?>">
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email"
                   value="<?php echo htmlspecialchars($pessoa['email'] ?? ''); ?>">
        </div>
        <div class="campo">
            <label for="cargo">Perfil do sistema</label>
            <select id="cargo" name="cargo" required>
                <option value="membro" <?php echo ($pessoa['cargo'] === 'membro') ? 'selected' : ''; ?>>Membro</option>
                <option value="admin" <?php echo ($pessoa['cargo'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="data_nascimento">Data de nascimento</label>
            <input type="date" id="data_nascimento" name="data_nascimento" required
                   value="<?php echo htmlspecialchars($pessoa['data_nascimento'] ?? ''); ?>">
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
                <?php foreach ($estadosCivis as $valor => $label): ?>
                    <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo (($pessoa['estado_civil'] ?? '') === $valor) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo" id="campo_nome_conjuge">
            <label for="nome_conjuge">Nome do parceiro</label>
            <input type="text" id="nome_conjuge" name="nome_conjuge"
                   pattern="^[A-Za-zÀ-ÿ\s]+$"
                   title="Digite apenas letras e espaços."
                   value="<?php echo htmlspecialchars($pessoa['nome_conjuge'] ?? ''); ?>">
        </div>
    </div>

    <div class="campo">
        <div class="checkbox-item">
            <input type="checkbox" id="eh_lider" name="eh_lider" value="1"
                   <?php echo ((int) ($pessoa['eh_lider'] ?? 0) === 1) ? 'checked' : ''; ?>>
            <label for="eh_lider">É líder</label>
        </div>
    </div>

    <div class="grid" id="bloco_lideranca">
        <div class="campo">
            <div class="checkbox-item">
                <input type="checkbox" id="lider_grupo_familiar" name="lider_grupo_familiar" value="1"
                       <?php echo ((int) ($pessoa['lider_grupo_familiar'] ?? 0) === 1) ? 'checked' : ''; ?>>
                <label for="lider_grupo_familiar">Líder de Grupo Familiar</label>
            </div>
        </div>
        <div class="campo">
            <div class="checkbox-item">
                <input type="checkbox" id="lider_departamento" name="lider_departamento" value="1"
                       <?php echo ((int) ($pessoa['lider_departamento'] ?? 0) === 1) ? 'checked' : ''; ?>>
                <label for="lider_departamento">Líder de Departamento</label>
            </div>
        </div>
    </div>

    <div class="campo">
        <label for="grupo_familiar_id">Grupo Familiar que pertence</label>
        <select id="grupo_familiar_id" name="grupo_familiar_id">
            <option value="">Não vincular agora</option>
            <?php foreach ($gruposFamiliares as $grupo): ?>
                <option value="<?php echo (int) $grupo['id']; ?>" <?php echo ((int) ($pessoa['grupo_familiar_id'] ?? 0) === (int) $grupo['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($grupo['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="telefone_fixo">Contato fixo</label>
            <input type="text" id="telefone_fixo" name="telefone_fixo"
                   inputmode="numeric" maxlength="11" pattern="\d{10,11}"
                   value="<?php echo htmlspecialchars($pessoa['telefone_fixo'] ?? ''); ?>">
        </div>
        <div class="campo">
            <label for="telefone_movel">Contato móvel</label>
            <input type="text" id="telefone_movel" name="telefone_movel"
                   inputmode="numeric" maxlength="11" pattern="\d{11}"
                   value="<?php echo htmlspecialchars($pessoa['telefone_movel'] ?? ''); ?>">
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="concluiu_integracao">Concluiu integração?</label>
            <select id="concluiu_integracao" name="concluiu_integracao" required>
                <option value="1" <?php echo ((int) ($pessoa['concluiu_integracao'] ?? 0) === 1) ? 'selected' : ''; ?>>Sim</option>
                <option value="0" <?php echo ((int) ($pessoa['concluiu_integracao'] ?? 0) === 0) ? 'selected' : ''; ?>>Não</option>
            </select>
        </div>
        <div class="campo">
            <label for="participou_retiro_integracao">Já participou do retiro de integração?</label>
            <select id="participou_retiro_integracao" name="participou_retiro_integracao" required>
                <option value="1" <?php echo ((int) ($pessoa['participou_retiro_integracao'] ?? 0) === 1) ? 'selected' : ''; ?>>Sim</option>
                <option value="0" <?php echo ((int) ($pessoa['participou_retiro_integracao'] ?? 0) === 0) ? 'selected' : ''; ?>>Não</option>
            </select>
        </div>
    </div>

    <div class="campo">
        <label for="nova_senha">Nova senha</label>
        <input type="password" id="nova_senha" name="nova_senha" minlength="8">
        <small>Deixe em branco para não alterar. Mínimo de 8 caracteres, com letra maiúscula, minúscula, número e símbolo.</small>
    </div>

    <div class="campo">
        <label for="confirmar_senha">Confirmar nova senha</label>
        <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8">
    </div>

    <button type="submit">Salvar alterações</button>
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
