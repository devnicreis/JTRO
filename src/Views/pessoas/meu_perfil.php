<?php require __DIR__ . '/../layouts/header.php'; ?>

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
    <h2>Dados da conta</h2>

    <div class="grid-perfil">
        <div class="campo">
            <label>Nome</label>
            <input type="text" value="<?php echo htmlspecialchars($pessoa['nome']); ?>" readonly>
        </div>

        <div class="campo">
            <label>CPF</label>
            <input type="text" value="<?php echo htmlspecialchars($pessoa['cpf']); ?>" readonly>
        </div>
    </div>

    <div class="campo">
        <label>Perfil do sistema</label>
        <input type="text" value="<?php echo htmlspecialchars($pessoa['cargo']); ?>" readonly>
    </div>
</div>

<div
    id="secao-troca-senha"
    class="card-perfil<?php echo $destacarTrocaSenha ? ' card-perfil-destaque' : ''; ?>"
    <?php echo $destacarTrocaSenha ? 'tabindex="-1"' : ''; ?>
>
    <h2>Seguranca da conta</h2>

    <?php if ($destacarTrocaSenha): ?>
        <div class="perfil-alerta-senha">
            <strong>Próximo passo obrigatório:</strong> defina sua nova senha nesta seção para concluir o primeiro acesso.
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
    <h2>E-mail de recuperacao</h2>

    <form method="POST" action="/meu_perfil.php<?php echo $forcarTroca ? '?forcar_troca=1' : ''; ?>">
        <input type="hidden" name="acao" value="atualizar_email">

        <div class="campo">
            <label for="email">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?php echo htmlspecialchars($pessoa['email'] ?? ''); ?>"
                required
            >
            <small>Esse e-mail sera usado para recuperacao de senha.</small>
        </div>

        <button type="submit">Atualizar e-mail</button>
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

<?php if ($destacarTrocaSenha): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
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
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
