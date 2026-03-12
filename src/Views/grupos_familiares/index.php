<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxLocalFixo = document.getElementById('local_fixo');
        const inputLocalPadrao = document.getElementById('local_padrao');

        function atualizarObrigatoriedadeLocalPadrao() {
            inputLocalPadrao.required = checkboxLocalFixo.checked;
        }

        atualizarObrigatoriedadeLocalPadrao();
        checkboxLocalFixo.addEventListener('change', atualizarObrigatoriedadeLocalPadrao);
    });
</script>

<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/index.php">← Voltar para início</a>
</div>

<h1>Cadastro de Grupos Familiares</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="/grupos_familiares.php">
    <div class="campo">
        <label for="nome">Nome do Grupo Familiar</label>
        <input
            type="text"
            id="nome"
            name="nome"
            required
            value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
    </div>

    <div class="grid">
        <div class="campo">
            <label for="dia_semana">Dia da semana</label>
            <select id="dia_semana" name="dia_semana" required>
                <option value="">Selecione</option>
                <?php
                $dias = ['segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
                $diaSelecionado = $_POST['dia_semana'] ?? '';
                foreach ($dias as $dia):
                ?>
                    <option value="<?php echo $dia; ?>" <?php echo $diaSelecionado === $dia ? 'selected' : ''; ?>>
                        <?php echo ucfirst($dia); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="horario">Horário</label>
            <input
                type="time"
                id="horario"
                name="horario"
                required
                value="<?php echo htmlspecialchars($_POST['horario'] ?? ''); ?>">
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label for="local_padrao">Local padrão</label>
            <input
                type="text"
                id="local_padrao"
                name="local_padrao"
                value="<?php echo htmlspecialchars($_POST['local_padrao'] ?? ''); ?>">
        </div>

        <div class="campo">
            <div class="campo-checkbox">
                <input
                    type="checkbox"
                    id="local_fixo"
                    name="local_fixo"
                    value="1"
                    <?php echo isset($_POST['local_fixo']) ? 'checked' : ''; ?>>
                <label for="local_fixo">Este GF possui local fixo</label>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="campo">
            <label>Líderes</label>
            <div class="checkbox-lista">
                <?php foreach ($pessoas as $pessoa): ?>
                    <div class="checkbox-item">
                        <input
                            type="checkbox"
                            id="lider_<?php echo $pessoa['id']; ?>"
                            name="lideres[]"
                            value="<?php echo $pessoa['id']; ?>"
                            <?php echo in_array((string)$pessoa['id'], $_POST['lideres'] ?? [], true) ? 'checked' : ''; ?>>
                        <label for="lider_<?php echo $pessoa['id']; ?>">
                            <?php echo htmlspecialchars($pessoa['nome']); ?> (<?php echo htmlspecialchars($pessoa['cargo']); ?>)
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="campo">
            <label>Membros</label>
            <div class="checkbox-lista">
                <?php foreach ($pessoas as $pessoa): ?>
                    <div class="checkbox-item">
                        <input
                            type="checkbox"
                            id="membro_<?php echo $pessoa['id']; ?>"
                            name="membros[]"
                            value="<?php echo $pessoa['id']; ?>"
                            <?php echo in_array((string)$pessoa['id'], $_POST['membros'] ?? [], true) ? 'checked' : ''; ?>>
                        <label for="membro_<?php echo $pessoa['id']; ?>">
                            <?php echo htmlspecialchars($pessoa['nome']); ?> (<?php echo htmlspecialchars($pessoa['cargo']); ?>)
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <button type="submit">Cadastrar Grupo Familiar</button>
</form>

<h2>Grupos Familiares cadastrados</h2>

<?php if (count($grupos) === 0): ?>
    <p>Nenhum Grupo Familiar cadastrado ainda.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Dia</th>
                <th>Horário</th>
                <th>Líderes</th>
                <th>Total de membros</th>
                <th>Local padrão</th>
                <th>Local fixo</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grupos as $grupo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($grupo['id']); ?></td>
                    <td><?php echo htmlspecialchars($grupo['nome']); ?></td>
                    <td><?php echo htmlspecialchars($grupo['dia_semana']); ?></td>
                    <td><?php echo htmlspecialchars($grupo['horario']); ?></td>
                    <td><?php echo htmlspecialchars($grupo['lideres'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($grupo['total_membros']); ?></td>
                    <td><?php echo htmlspecialchars($grupo['local_padrao'] ?? ''); ?></td>
                    <td><?php echo (int)$grupo['local_fixo'] === 1 ? 'Sim' : 'Não'; ?></td>
                    <td>
                        <?php if ((int)$grupo['ativo'] === 1): ?>
                            <span class="status-ativo">Ativo</span>
                        <?php else: ?>
                            <span class="status-inativo">Desativado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="acoes">
                            <a class="botao-link" href="/grupos_familiares_editar.php?id=<?php echo $grupo['id']; ?>">Editar</a>

                            <?php if ((int)$grupo['ativo'] === 1): ?>
                                <form method="POST" action="/grupos_familiares_desativar.php" class="form-acao" onsubmit="return confirm('Deseja realmente desativar este GF?');">
                                    <input type="hidden" name="id" value="<?php echo $grupo['id']; ?>">
                                    <button type="submit" class="botao-desativar-gf">Desativar</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="/grupos_familiares_reativar.php" class="form-acao" onsubmit="return confirm('Deseja realmente reativar este GF?');">
                                    <input type="hidden" name="id" value="<?php echo $grupo['id']; ?>">
                                    <button type="submit" class="botao-reativar-gf">Reativar</button>
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