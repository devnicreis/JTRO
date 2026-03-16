<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="menu">
    <a href="/index.php">← Voltar para início</a>
</div>

<h1>Reuniões e Presenças</h1>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="GET" action="/presencas.php">
    <div class="grid">
        <div class="campo">
            <label for="grupo_id">Grupo Familiar</label>
            <select id="grupo_id" name="grupo_id" required>
                <option value="">Selecione</option>
                <?php foreach ($grupos as $grupo): ?>
                    <option
                        value="<?php echo $grupo['id']; ?>"
                        <?php echo $grupoId === (int)$grupo['id'] ? 'selected' : ''; ?>
                    >
                        <?php echo htmlspecialchars($grupo['nome']); ?> — <?php echo htmlspecialchars($grupo['dia_semana']); ?> às <?php echo htmlspecialchars($grupo['horario']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="data">Data da reunião</label>
            <input
                type="date"
                id="data"
                name="data"
                required
                value="<?php echo htmlspecialchars($data); ?>"
            >
            <div id="erro-data" class="erro" style="display:none; margin-top: 8px;">
                A reunião só pode ser criada para hoje ou até 30 dias atrás.
            </div>
        </div>
    </div>

    <button type="submit">Carregar reunião</button>
</form>

<div class="presencas-layout">
    <div class="presencas-coluna">
        <?php if ($reuniao && count($listaPresencas) > 0): ?>
            <div class="presencas-card">
                <form method="POST" action="/presencas.php">
                    <input type="hidden" name="salvar_presencas" value="1">
                    <input type="hidden" name="reuniao_id" value="<?php echo $reuniao['id']; ?>">
                    <input type="hidden" name="grupo_id" value="<?php echo $grupoId; ?>">
                    <input type="hidden" name="data" value="<?php echo htmlspecialchars($data); ?>">

                    <h2><?php echo htmlspecialchars($reuniao['grupo_nome']); ?> — <?php echo htmlspecialchars($reuniao['data']); ?></h2>

                    <div class="grid">
                        <div class="campo">
                            <label for="local">Local</label>
                            <input
                                type="text"
                                id="local"
                                name="local"
                                maxlength="25"
                                value="<?php echo htmlspecialchars($reuniao['local'] ?? ''); ?>"
                                <?php echo (int)$reuniao['local_fixo'] === 1 ? 'readonly' : ''; ?>
                            >
                        </div>

                        <div class="campo">
                            <label for="horario_info">Horário</label>
                            <input
                                type="text"
                                id="horario_info"
                                value="<?php echo htmlspecialchars($reuniao['horario']); ?>"
                                readonly
                            >
                        </div>
                    </div>

                    <div class="campo">
                        <label for="observacoes">Observações</label>
                        <textarea id="observacoes" name="observacoes" maxlength="255"><?php echo htmlspecialchars($reuniao['observacoes'] ?? ''); ?></textarea>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Perfil do sistema</th>
                                <th>Presença</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listaPresencas as $presenca): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($presenca['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($presenca['cpf']); ?></td>
                                    <td><?php echo htmlspecialchars($presenca['cargo']); ?></td>
                                    <td>
                                        <div class="status-group">
                                            <label>
                                                <input
                                                    type="radio"
                                                    name="presencas[<?php echo $presenca['id']; ?>]"
                                                    value="presente"
                                                    <?php echo $presenca['status'] === 'presente' ? 'checked' : ''; ?>
                                                >
                                                Presente
                                            </label>

                                            <label>
                                                <input
                                                    type="radio"
                                                    name="presencas[<?php echo $presenca['id']; ?>]"
                                                    value="ausente"
                                                    <?php echo $presenca['status'] === 'ausente' ? 'checked' : ''; ?>
                                                >
                                                Ausente
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="submit">Salvar presenças</button>
                </form>
            </div>
        <?php elseif ($grupoId > 0 && $data !== '' && $erro === ''): ?>
            <div class="bloco-criar-reuniao">
                <div class="erro">
                    Não existe reunião registrada para essa data.
                </div>

                <form method="POST" action="/presencas.php" class="card-formulario">
                    <input type="hidden" name="criar_reuniao" value="1">
                    <input type="hidden" name="grupo_id" value="<?php echo $grupoId; ?>">
                    <input type="hidden" name="data" value="<?php echo htmlspecialchars($data); ?>">

                    <div class="campo">
                        <label for="horario_criacao">Horário da reunião</label>
                        <input
                            type="time"
                            id="horario_criacao"
                            name="horario_criacao"
                            value="<?php echo isset($resumoGrupo['horario']) ? htmlspecialchars($resumoGrupo['horario']) : ''; ?>"
                            required
                        >
                        <small>
                            Se o horário informado for diferente do horário padrão do GF, a reunião será sinalizada no sistema.
                        </small>
                    </div>

                    <div class="campo">
                        <small>
                            A reunião só pode ser criada para hoje ou até 30 dias atrás. Se a data escolhida não corresponder ao dia padrão do GF, ela será sinalizada no sistema.
                        </small>
                    </div>

                    <button type="submit">Criar reunião desta data</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="presencas-coluna">
        <?php if ($grupoId > 0 && !empty($resumoGrupo)): ?>
            <div class="quadro-reunioes">
                <h2>Resumo do Grupo Familiar</h2>

                <div class="cards-resumo">
                    <div class="card-resumo">
                        <h3>Membros ativos</h3>
                        <div class="numero"><?php echo htmlspecialchars($resumoGrupo['total_membros_ativos'] ?? 0); ?></div>
                    </div>

                    <div class="card-resumo">
                        <h3>Total de reuniões</h3>
                        <div class="numero"><?php echo htmlspecialchars($resumoGrupo['total_reunioes'] ?? 0); ?></div>
                    </div>

                    <div class="card-resumo">
                        <h3>Última reunião</h3>
                        <div class="numero" style="font-size: 16px;">
                            <?php echo htmlspecialchars($resumoGrupo['ultima_data_reuniao'] ?? '—'); ?>
                        </div>
                    </div>

                    <div class="card-resumo">
                        <h3>Local padrão</h3>
                        <div class="numero" style="font-size: 16px;">
                            <?php echo htmlspecialchars($resumoGrupo['local_padrao'] ?? '—'); ?>
                        </div>
                    </div>
                </div>

                <h2>Últimas reuniões</h2>

                <?php if (count($ultimasReunioes) === 0): ?>
                    <p>Ainda não há reuniões registradas para este GF.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Local</th>
                                <th>Presentes</th>
                                <th>Ausentes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasReunioes as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['data']); ?></td>
                                    <td><?php echo htmlspecialchars($item['horario']); ?></td>
                                    <td><?php echo htmlspecialchars($item['local'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['total_presentes']); ?></td>
                                    <td><?php echo htmlspecialchars($item['total_ausentes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const campoLocal = document.getElementById('local');
    const campoData = document.getElementById('data');
    const erroData = document.getElementById('erro-data');

    if (campoLocal && !campoLocal.hasAttribute('readonly')) {
        campoLocal.required = true;
    }

    if (campoData) {
        const hoje = new Date();
        const ano = hoje.getFullYear();
        const mes = String(hoje.getMonth() + 1).padStart(2, '0');
        const dia = String(hoje.getDate()).padStart(2, '0');
        const hojeStr = `${ano}-${mes}-${dia}`;

        const limitePassado = new Date(hoje);
        limitePassado.setDate(limitePassado.getDate() - 30);

        const anoMin = limitePassado.getFullYear();
        const mesMin = String(limitePassado.getMonth() + 1).padStart(2, '0');
        const diaMin = String(limitePassado.getDate()).padStart(2, '0');
        const minStr = `${anoMin}-${mesMin}-${diaMin}`;

        campoData.min = minStr;
        campoData.max = hojeStr;

        campoData.addEventListener('change', function () {
            const valor = this.value;

            if (valor !== '' && (valor < minStr || valor > hojeStr)) {
                if (erroData) {
                    erroData.style.display = 'block';
                }
                this.value = '';
            } else {
                if (erroData) {
                    erroData.style.display = 'none';
                }
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>