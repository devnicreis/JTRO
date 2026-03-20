<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <h1>Cadastro de Pessoas</h1>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/pessoas.php">
    <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" required
               pattern="^[A-Za-zÀ-ÿ\s]+$"
               title="Digite apenas letras e espaços."
               value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
        <small>Digite somente letras e espaços.</small>
    </div>

    <div class="campo">
        <label for="cpf">CPF</label>
        <input type="text" id="cpf" name="cpf" required
               inputmode="numeric" maxlength="11" pattern="\d{11}"
               title="Digite somente números, sem pontos e traços."
               value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>">
        <small>Digite somente números, sem pontos e traços.</small>
    </div>

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
            <option value="admin"  <?php echo (($_POST['cargo'] ?? '') === 'admin')  ? 'selected' : ''; ?>>Admin</option>
        </select>
    </div>

    <button type="submit">Cadastrar pessoa</button>
</form>

<h2 style="margin-top:32px; margin-bottom:16px;">Pessoas cadastradas</h2>

<?php if (count($pessoas) === 0): ?>
    <p style="color:var(--color-text-muted); font-size:14px;">Nenhuma pessoa cadastrada ainda.</p>
<?php else: ?>
    <div class="tabela-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pessoas as $registro): ?>
                    <?php $ativo = (int)$registro['ativo'] === 1; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($registro['id']); ?></td>
                        <td><?php echo htmlspecialchars($registro['nome']); ?></td>
                        <td><?php echo htmlspecialchars($registro['cpf']); ?></td>
                        <td><?php echo htmlspecialchars($registro['email'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($registro['cargo']); ?></td>
                        <td>
                            <?php if ($ativo): ?>
                                <span class="status-ativo">Ativo</span>
                            <?php else: ?>
                                <span class="status-inativo">Desativado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="acoes" style="flex-direction:column; gap:6px; align-items:stretch;">
                                <a class="btn-gf btn-gf-editar"
                                   href="/pessoas_editar.php?id=<?php echo $registro['id']; ?>">
                                    Editar
                                </a>

                                <?php if ($ativo): ?>
                                    <form method="POST" action="/pessoas_desativar.php"
                                          class="form-acao"
                                          onsubmit="return confirm('Deseja realmente desativar esta pessoa?');">
                                        <input type="hidden" name="id" value="<?php echo $registro['id']; ?>">
                                        <button type="submit" class="btn-gf btn-gf-desativar">Desativar</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/pessoas_reativar.php"
                                          class="form-acao"
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
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
