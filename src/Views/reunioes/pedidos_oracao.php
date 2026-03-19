<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/presencas.php?grupo_id=<?php echo (int) $reuniao['grupo_familiar_id']; ?>&data=<?php echo urlencode($reuniao['data']); ?>">
        ← Voltar para a reunião
    </a>
    |
    <a href="/index.php">Voltar para início</a>
</div>

<h1>Pedidos de Oração</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<div class="presencas-card">
    <h2><?php echo htmlspecialchars($reuniao['grupo_nome']); ?></h2>
    <p><strong>Data da reunião:</strong> <?php echo htmlspecialchars(formatarDataBr($reuniao['data'])); ?> às <?php echo htmlspecialchars($reuniao['horario']); ?></p>

    <?php if (empty($presentes)): ?>
        <p>Não há membros presentes nesta reunião.</p>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="reuniao_id" value="<?php echo (int) $reuniaoId; ?>">

            <?php foreach ($presentes as $presente): ?>
                <div class="campo" style="margin-bottom: 16px;">
                    <label for="pedido_<?php echo (int) $presente['pessoa_id']; ?>">
                        <?php echo htmlspecialchars($presente['nome']); ?>
                    </label>
                    <textarea
                        id="pedido_<?php echo (int) $presente['pessoa_id']; ?>"
                        name="pedidos[<?php echo (int) $presente['pessoa_id']; ?>]"
                        maxlength="500"><?php echo htmlspecialchars($pedidosMap[(int) $presente['pessoa_id']] ?? ''); ?></textarea>
                </div>
            <?php endforeach; ?>

            <button type="submit">Salvar pedidos de oração</button>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>