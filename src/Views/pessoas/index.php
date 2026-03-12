<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/index.php">← Voltar para início</a>
</div>

<h1>Cadastro de Pessoas</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/pessoas.php">
    <div class="campo">
        <label for="nome">Nome</label>
        <input
            type="text"
            id="nome"
            name="nome"
            required
            pattern="^[A-Za-zÀ-ÿ\s]+$"
            title="Digite apenas letras e espaços."
            value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
        >
        <small>Digite somente letras e espaços.</small>
    </div>

    <div class="campo">
        <label for="cpf">CPF</label>
        <input
            type="text"
            id="cpf"
            name="cpf"
            required
            inputmode="numeric"
            maxlength="11"
            pattern="\d{11}"
            title="Digite somente números, sem pontos e traços."
            value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>"
        >
        <small>Digite somente números, sem pontos e traços.</small>
    </div>

    <div class="campo">
        <label for="cargo">Perfil do sistema</label>
        <select id="cargo" name="cargo" required>
            <option value="">Selecione</option>
            <option value="membro" <?php echo (($_POST['cargo'] ?? '') === 'membro') ? 'selected' : ''; ?>>Membro</option>
            <option value="admin" <?php echo (($_POST['cargo'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
        </select>
    </div>

    <button type="submit">Cadastrar pessoa</button>
</form>

<h2>Pessoas cadastradas</h2>

<?php if (count($pessoas) === 0): ?>
    <p>Nenhuma pessoa cadastrada ainda.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Perfil do sistema</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pessoas as $registro): ?>
                <tr>
                    <td><?php echo htmlspecialchars($registro['id']); ?></td>
                    <td><?php echo htmlspecialchars($registro['nome']); ?></td>
                    <td><?php echo htmlspecialchars($registro['cpf']); ?></td>
                    <td><?php echo htmlspecialchars($registro['cargo']); ?></td>
                    <td>
                        <?php if ((int)$registro['ativo'] === 1): ?>
                            <span class="status-ativo">Ativo</span>
                        <?php else: ?>
                            <span class="status-inativo">Desativado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="acoes">
                            <a class="botao-editar" href="/pessoas_editar.php?id=<?php echo $registro['id']; ?>">Editar</a>

                            <?php if ((int)$registro['ativo'] === 1): ?>
                                <form
                                    method="POST"
                                    action="/pessoas_desativar.php"
                                    class="form-acao"
                                    onsubmit="return confirm('Deseja realmente desativar esta pessoa?');"
                                >
                                    <input type="hidden" name="id" value="<?php echo $registro['id']; ?>">
                                    <button type="submit" class="botao-desativar">Desativar</button>
                                </form>
                            <?php else: ?>
                                <form
                                    method="POST"
                                    action="/pessoas_reativar.php"
                                    class="form-acao"
                                    onsubmit="return confirm('Deseja realmente reativar esta pessoa?');"
                                >
                                    <input type="hidden" name="id" value="<?php echo $registro['id']; ?>">
                                    <button type="submit" class="botao-reativar">Reativar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>