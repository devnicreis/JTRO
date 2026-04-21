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

    <?php if (empty($camposPedidos)): ?>
        <p>Não há membros presentes nesta reunião.</p>
    <?php else: ?>
        <div class="pedidos-oracao-acoes">
            <button type="button" class="btn-presenca-oracao btn-copiar-pedidos" data-copiar-pedidos>
                Copiar Pedidos de Oração
            </button>
        </div>

        <form method="POST" id="formPedidosOracao">
            <input type="hidden" name="reuniao_id" value="<?php echo (int) $reuniaoId; ?>">

            <?php foreach ($camposPedidos as $campoPedido): ?>
                <?php
                $campoId = (string) ($campoPedido['campo_id'] ?? '');
                $pedidoAtual = (string) ($campoPedido['pedido'] ?? '');
                $rotulo = (string) ($campoPedido['rotulo'] ?? 'Membro');
                $compartilhadoCasal = !empty($campoPedido['compartilhado_casal']);
                ?>
                <div class="campo pedido-oracao-campo" style="margin-bottom: 16px;">
                    <label for="pedido_<?php echo htmlspecialchars($campoId); ?>">
                        <?php echo htmlspecialchars($rotulo); ?>
                        <?php if ($compartilhadoCasal): ?>
                            <small style="font-weight:400; color:var(--color-text-muted);">(pedido compartilhado)</small>
                        <?php endif; ?>
                    </label>
                    <textarea
                        id="pedido_<?php echo htmlspecialchars($campoId); ?>"
                        name="pedidos[<?php echo htmlspecialchars($campoId); ?>]"
                        class="textarea-auto-grow pedido-oracao-textarea"
                        data-rotulo="<?php echo htmlspecialchars($rotulo); ?>"
                        maxlength="500"><?php echo htmlspecialchars($pedidoAtual); ?></textarea>
                </div>
            <?php endforeach; ?>

            <div class="pedidos-oracao-acoes">
                <button type="submit">Salvar pedidos de oração</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('.textarea-auto-grow');

    function ajustarAltura(el) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }

    textareas.forEach(function(textarea) {
        ajustarAltura(textarea);
        textarea.addEventListener('input', function() {
            ajustarAltura(textarea);
        });
    });

    function construirTextoPedidos() {
        const linhas = [];
        const pedidos = document.querySelectorAll('.pedido-oracao-textarea');

        pedidos.forEach(function(textarea) {
            const texto = textarea.value.trim();
            if (texto === '') {
                return;
            }

            const rotulo = (textarea.dataset.rotulo || 'Membro').trim();
            linhas.push(rotulo + ': ' + texto);
        });

        const cabecalho = 'Pedidos de oração - <?php echo addslashes((string) $reuniao['grupo_nome']); ?> (<?php echo addslashes((string) formatarDataBr($reuniao['data'])); ?> às <?php echo addslashes((string) $reuniao['horario']); ?>)';
        if (linhas.length === 0) {
            return cabecalho + "\n\nSem pedidos preenchidos.";
        }

        return cabecalho + "\n\n" + linhas.map(function(linha, idx) {
            return (idx + 1) + '. ' + linha;
        }).join("\n\n");
    }

    function copiarTexto(texto) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(texto);
        }

        return new Promise(function(resolve, reject) {
            const campoTmp = document.createElement('textarea');
            campoTmp.value = texto;
            campoTmp.setAttribute('readonly', 'readonly');
            campoTmp.style.position = 'absolute';
            campoTmp.style.left = '-9999px';
            document.body.appendChild(campoTmp);
            campoTmp.select();

            try {
                document.execCommand('copy');
                document.body.removeChild(campoTmp);
                resolve();
            } catch (err) {
                document.body.removeChild(campoTmp);
                reject(err);
            }
        });
    }

    document.querySelectorAll('[data-copiar-pedidos]').forEach(function(botao) {
        botao.addEventListener('click', function() {
            copiarTexto(construirTextoPedidos())
                .then(function() {
                    window.alert('Pedidos de oração copiados.');
                })
                .catch(function() {
                    window.alert('Não foi possível copiar os pedidos de oração.');
                });
        });
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
