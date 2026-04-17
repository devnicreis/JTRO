<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<?php
$destinosChamado = opcoesDestinoChamado();
$assuntosSecretaria = opcoesAssuntoSecretaria();
$assuntosSuporte = opcoesAssuntoSuporte();
$camposPessoaChamado = opcoesCamposPessoaChamado();
$camposGFChamado = opcoesCamposGFChamado();
$motivosPessoaChamado = opcoesMotivoDesativacaoPessoa();
$telasSuporteChamado = opcoesTelasSuporte();
?>

<div class="page-header">
    <h1>Chamados</h1>
</div>

<?php if ($mensagem !== ''): ?>
    <div class="mensagem">
        <?php echo htmlspecialchars($mensagem); ?>
        <?php if ($numeroChamadoCriado !== null): ?>
            <div class="notif-detalhe" style="margin-top:6px;">O número do chamado é: <?php echo htmlspecialchars($numeroChamadoCriado); ?>.</div>
            <div class="notif-detalhe"><a href="/chamados.php">Consultar meus chamados</a> / <a href="/index.php">Voltar à tela inicial</a></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<?php if (!$isAdmin): ?>
    <?php if ($previewChamado === null): ?>
    <div class="presencas-card">
        <h2>Abrir um chamado</h2>

        <form method="POST" action="/chamados.php" id="formChamadoLider">
            <div class="grid">
                <div class="campo">
                    <label for="destino">Destino da solicitação</label>
                    <select id="destino" name="destino" required>
                        <option value="">Selecione</option>
                        <?php foreach ($destinosChamado as $valor => $label): ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo ($formChamado['destino'] ?? '') === $valor ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="assunto_tipo">Assunto da solicitação</label>
                    <select id="assunto_tipo" name="assunto_tipo" required>
                        <option value="">Selecione</option>
                        <?php foreach ($assuntosSecretaria as $valor => $label): ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" data-destino="secretaria" <?php echo ($formChamado['assunto_tipo'] ?? '') === $valor ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php foreach ($assuntosSuporte as $valor => $label): ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" data-destino="suporte" <?php echo ($formChamado['assunto_tipo'] ?? '') === $valor ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="bloco_pessoa" class="grid">
                <div class="campo">
                    <label for="pessoa_id">Selecione a pessoa</label>
                    <select id="pessoa_id" name="pessoa_id">
                        <option value="">Selecione</option>
                        <?php foreach ($pessoasPermitidasLista as $pessoa): ?>
                            <option value="<?php echo (int) $pessoa['id']; ?>" <?php echo (int) ($formChamado['pessoa_id'] ?? 0) === (int) $pessoa['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pessoa['nome'] . ' — ' . ($pessoa['grupo_nome'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo" id="bloco_campo_pessoa">
                    <label for="campo_alteracao_pessoa">Informe o que precisa ser alterado</label>
                    <select id="campo_alteracao_pessoa" data-target="campo_alteracao">
                        <option value="">Selecione</option>
                        <?php foreach ($camposPessoaChamado as $valor => $label): ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo ($formChamado['campo_alteracao'] ?? '') === $valor ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="bloco_motivo_pessoa">
                <div class="campo">
                    <label for="motivo_desativacao_tipo">Informe o motivo da desativação</label>
                    <select id="motivo_desativacao_tipo" name="motivo_desativacao_tipo">
                        <option value="">Selecione</option>
                        <?php foreach ($motivosPessoaChamado as $valor => $label): ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo ($formChamado['motivo_desativacao_tipo'] ?? '') === $valor ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid">
                    <div class="campo" id="bloco_motivo_detalhe">
                        <label for="motivo_desativacao_detalhe">Para qual?</label>
                        <input type="text" id="motivo_desativacao_detalhe" name="motivo_desativacao_detalhe" maxlength="100"
                               value="<?php echo htmlspecialchars($formChamado['motivo_desativacao_detalhe'] ?? ''); ?>">
                    </div>
                    <div class="campo" id="bloco_motivo_texto">
                        <label for="motivo_desativacao_texto">Resuma</label>
                        <input type="text" id="motivo_desativacao_texto" name="motivo_desativacao_texto" maxlength="250"
                               value="<?php echo htmlspecialchars($formChamado['motivo_desativacao_texto'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div id="bloco_gf" class="grid">
                <div class="campo">
                    <label for="grupo_familiar_id">Selecione o GF</label>
                    <select id="grupo_familiar_id" name="grupo_familiar_id">
                        <option value="">Selecione</option>
                        <?php foreach ($gruposPermitidosLista as $grupo): ?>
                            <option value="<?php echo (int) $grupo['id']; ?>" <?php echo (int) ($formChamado['grupo_familiar_id'] ?? 0) === (int) $grupo['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grupo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo" id="bloco_campo_gf">
                    <label for="campo_alteracao_gf">Informe o que precisa ser alterado</label>
                    <select id="campo_alteracao_gf" data-target="campo_alteracao">
                        <option value="">Selecione</option>
                        <?php foreach ($camposGFChamado as $valor => $label): ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo ($formChamado['campo_alteracao'] ?? '') === $valor ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="bloco_suporte_tela" class="campo">
                <label for="tela_problema">Tela com problema</label>
                <select id="tela_problema" name="tela_problema">
                    <option value="">Selecione</option>
                    <?php foreach ($telasSuporteChamado as $valor => $label): ?>
                        <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo ($formChamado['tela_problema'] ?? '') === $valor ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="hidden" name="campo_alteracao" id="campo_alteracao_hidden" value="<?php echo htmlspecialchars($formChamado['campo_alteracao'] ?? ''); ?>">

            <div class="campo">
                <label for="resumo_solicitacao">Resuma sua solicitação</label>
                <textarea id="resumo_solicitacao" name="resumo_solicitacao" maxlength="1000" style="min-height:120px;"><?php echo htmlspecialchars($formChamado['resumo_solicitacao'] ?? ''); ?></textarea>
            </div>

            <button type="submit" name="acao" value="pre_visualizar">Próximo Passo</button>
        </form>
    </div>
    <?php else: ?>
    <div class="presencas-card">
            <h2>Resumo de sua solicitação</h2>

            <div class="campo">
                <label>Nome do solicitante</label>
                <p><?php echo htmlspecialchars($previewChamado['nome_solicitante']); ?></p>
            </div>
            <div class="campo">
                <label>Contato</label>
                <p><?php echo htmlspecialchars($previewChamado['contato_solicitante']); ?></p>
            </div>
            <div class="campo">
                <label>Solicitação</label>
                <p><?php echo htmlspecialchars($previewChamado['destino_label'] . ' / ' . $previewChamado['assunto_label']); ?></p>
            </div>
            <div class="campo">
                <label>Resumo da solicitação</label>
                <p><?php echo nl2br(htmlspecialchars($previewChamado['resumo_solicitacao'])); ?></p>
                <?php if (!empty($previewChamado['pessoa_label'])): ?><div class="notif-detalhe">Pessoa: <?php echo htmlspecialchars($previewChamado['pessoa_label']); ?></div><?php endif; ?>
                <?php if (!empty($previewChamado['grupo_label'])): ?><div class="notif-detalhe">GF: <?php echo htmlspecialchars($previewChamado['grupo_label']); ?></div><?php endif; ?>
                <?php if (!empty($previewChamado['campo_alteracao_label'])): ?><div class="notif-detalhe">Campo: <?php echo htmlspecialchars($previewChamado['campo_alteracao_label']); ?></div><?php endif; ?>
                <?php if (!empty($previewChamado['motivo_desativacao_label'])): ?><div class="notif-detalhe">Motivo: <?php echo htmlspecialchars($previewChamado['motivo_desativacao_label']); ?></div><?php endif; ?>
                <?php if (!empty($previewChamado['motivo_desativacao_detalhe'])): ?><div class="notif-detalhe">Detalhe: <?php echo htmlspecialchars($previewChamado['motivo_desativacao_detalhe']); ?></div><?php endif; ?>
                <?php if (!empty($previewChamado['motivo_desativacao_texto'])): ?><div class="notif-detalhe">Complemento: <?php echo htmlspecialchars($previewChamado['motivo_desativacao_texto']); ?></div><?php endif; ?>
                <?php if (!empty($previewChamado['tela_problema_label'])): ?><div class="notif-detalhe">Tela: <?php echo htmlspecialchars($previewChamado['tela_problema_label']); ?></div><?php endif; ?>
            </div>

            <form method="POST" action="/chamados.php" class="acoes" style="justify-content:flex-start;">
                <?php foreach ($formChamado as $chave => $valor): ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($chave); ?>" value="<?php echo htmlspecialchars((string) $valor); ?>">
                <?php endforeach; ?>
                <button type="submit" name="acao" value="abrir_chamado">Fazer solicitação</button>
                <button type="submit" name="acao" value="voltar_edicao" class="botao-link botao-secundario">Voltar e editar</button>
                <a class="botao-link botao-secundario" href="/chamados.php">Cancelar solicitação</a>
            </form>
        </div>
    <?php endif; ?>

    <div class="presencas-card">
        <h2>Consultar minhas solicitações</h2>

        <?php if (count($meusChamados) === 0): ?>
            <p style="color:var(--color-text-muted); font-size:14px;">Você ainda não abriu chamados.</p>
        <?php else: ?>
            <div class="tabela-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Assunto</th>
                            <th>Data da solicitação</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meusChamados as $chamado): ?>
                            <tr class="linha-chamado" data-detalhe="lider-<?php echo (int) $chamado['id']; ?>">
                                <td><button type="button" class="btn-visualizar btn-toggle-chamado"><?php echo htmlspecialchars($chamado['numero_formatado']); ?></button></td>
                                <td><?php echo htmlspecialchars($chamado['assunto_label']); ?></td>
                                <td><?php echo htmlspecialchars(formatarDataHoraBr($chamado['solicitado_em'])); ?></td>
                                <td><?php echo htmlspecialchars(labelStatusChamado($chamado['status'])); ?></td>
                            </tr>
                            <tr id="lider-<?php echo (int) $chamado['id']; ?>" style="display:none;">
                                <td colspan="4" style="background:var(--color-bg);">
                                    <div><strong>Destino:</strong> <?php echo htmlspecialchars(labelDestinoChamado($chamado['destino'])); ?></div>
                                    <div class="notif-detalhe">Resumo: <?php echo htmlspecialchars($chamado['resumo_solicitacao']); ?></div>
                                    <?php if (!empty($chamado['observacao_admin'])): ?>
                                        <div class="notif-detalhe">Observação do admin: <?php echo htmlspecialchars($chamado['observacao_admin']); ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="presencas-card">
        <h2>Consulta de chamados</h2>

        <?php if (count($chamadosAdmin) === 0): ?>
            <p style="color:var(--color-text-muted); font-size:14px;">Nenhum chamado aberto até o momento.</p>
        <?php else: ?>
            <div class="tabela-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Assunto</th>
                            <th>Data da solicitação</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chamadosAdmin as $chamado): ?>
                            <tr class="linha-chamado" data-detalhe="admin-<?php echo (int) $chamado['id']; ?>">
                                <td><button type="button" class="btn-visualizar btn-toggle-chamado"><?php echo htmlspecialchars($chamado['numero_formatado']); ?></button></td>
                                <td><?php echo htmlspecialchars($chamado['assunto_label']); ?></td>
                                <td><?php echo htmlspecialchars(formatarDataHoraBr($chamado['solicitado_em'])); ?></td>
                                <td><?php echo htmlspecialchars(labelStatusChamado($chamado['status'])); ?></td>
                            </tr>
                            <tr id="admin-<?php echo (int) $chamado['id']; ?>" style="display:none;">
                                <td colspan="4" style="background:var(--color-bg);">
                                    <div><strong>Solicitante:</strong> <?php echo htmlspecialchars($chamado['solicitante_nome']); ?></div>
                                    <div class="notif-detalhe">Contato: <?php echo htmlspecialchars(formatarTelefone($chamado['solicitante_telefone'] ?: $chamado['solicitante_telefone_fixo'])); ?></div>
                                    <div class="notif-detalhe">Destino: <?php echo htmlspecialchars(labelDestinoChamado($chamado['destino'])); ?></div>
                                    <?php if (!empty($chamado['pessoa_nome'])): ?><div class="notif-detalhe">Pessoa: <?php echo htmlspecialchars($chamado['pessoa_nome']); ?></div><?php endif; ?>
                                    <?php if (!empty($chamado['grupo_nome'])): ?><div class="notif-detalhe">GF: <?php echo htmlspecialchars($chamado['grupo_nome']); ?></div><?php endif; ?>
                                    <?php if (!empty($chamado['campo_alteracao'])): ?><div class="notif-detalhe">Campo: <?php echo htmlspecialchars(($camposPessoaChamado[$chamado['campo_alteracao']] ?? $camposGFChamado[$chamado['campo_alteracao']] ?? $chamado['campo_alteracao'])); ?></div><?php endif; ?>
                                    <?php if (!empty($chamado['tela_problema'])): ?><div class="notif-detalhe">Tela: <?php echo htmlspecialchars($telasSuporteChamado[$chamado['tela_problema']] ?? $chamado['tela_problema']); ?></div><?php endif; ?>
                                    <div class="notif-detalhe">Resumo: <?php echo htmlspecialchars($chamado['resumo_solicitacao']); ?></div>

                                    <form method="POST" action="/chamados.php" style="margin-top:12px;">
                                        <input type="hidden" name="acao" value="atualizar_status">
                                        <input type="hidden" name="chamado_id" value="<?php echo (int) $chamado['id']; ?>">
                                        <div class="campo">
                                            <label for="observacao_admin_<?php echo (int) $chamado['id']; ?>">Inserir observação</label>
                                            <textarea id="observacao_admin_<?php echo (int) $chamado['id']; ?>" name="observacao_admin" maxlength="500" style="min-height:90px;"><?php echo htmlspecialchars($chamado['observacao_admin'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="acoes">
                                            <button type="submit" name="status" value="concluido">Marcar como concluído</button>
                                            <button type="submit" name="status" value="cancelado" class="btn-gf btn-gf-desativar" style="width:auto;">Marcar como cancelado</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="presencas-card">
    <h2>Contatos</h2>

    <div class="campo">
        <label>Secretaria C. C. Abba Fazenda Rio Grande</label>
        <p>Telefone: <a href="https://wa.me/5541998640484" target="_blank" rel="noopener noreferrer">41 9 9864-0484</a></p>
        <p>E-mail: <a href="mailto:secretariaabbafazenda@gmail.com">secretariaabbafazenda@gmail.com</a></p>
    </div>

    <div class="campo">
        <label>Suporte Técnico</label>
        <p>Telefone: <a href="https://wa.me/554187288953" target="_blank" rel="noopener noreferrer">41 9 8728-8953</a></p>
        <p>E-mail: <a href="mailto:suporte@jtro.com.br">suporte@jtro.com.br</a></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const destino = document.getElementById('destino');
    const assunto = document.getElementById('assunto_tipo');
    const blocoPessoa = document.getElementById('bloco_pessoa');
    const blocoMotivoPessoa = document.getElementById('bloco_motivo_pessoa');
    const blocoGF = document.getElementById('bloco_gf');
    const blocoSuporteTela = document.getElementById('bloco_suporte_tela');
    const blocoCampoPessoa = document.getElementById('bloco_campo_pessoa');
    const blocoCampoGF = document.getElementById('bloco_campo_gf');
    const blocoMotivoDetalhe = document.getElementById('bloco_motivo_detalhe');
    const blocoMotivoTexto = document.getElementById('bloco_motivo_texto');
    const motivoTipo = document.getElementById('motivo_desativacao_tipo');
    const campoPessoa = document.getElementById('campo_alteracao_pessoa');
    const campoGF = document.getElementById('campo_alteracao_gf');
    const campoHidden = document.getElementById('campo_alteracao_hidden');

    function sincronizarCampoAlteracao() {
        if (!campoHidden) return;
        if (blocoCampoPessoa && blocoCampoPessoa.style.display !== 'none' && campoPessoa) {
            campoHidden.value = campoPessoa.value;
            return;
        }
        if (blocoCampoGF && blocoCampoGF.style.display !== 'none' && campoGF) {
            campoHidden.value = campoGF.value;
            return;
        }
        campoHidden.value = '';
    }

    function atualizarFormularioChamado() {
        if (!destino || !assunto) return;

        const destinoValor = destino.value;
        Array.from(assunto.options).forEach(function(option) {
            if (!option.value) return;
            option.hidden = option.dataset.destino !== destinoValor;
        });

        const assuntoValor = assunto.value;
        const pessoa = assuntoValor === 'edicao_pessoa' || assuntoValor === 'desativacao_pessoa';
        const gf = assuntoValor === 'edicao_gf' || assuntoValor === 'desativacao_gf';
        const suporteTela = destinoValor === 'suporte' && assuntoValor === 'problema_tela';
        const motivoPessoa = assuntoValor === 'desativacao_pessoa';
        const campoPessoaVisivel = assuntoValor === 'edicao_pessoa';
        const campoGFVisivel = assuntoValor === 'edicao_gf';

        if (blocoPessoa) blocoPessoa.style.display = pessoa ? '' : 'none';
        if (blocoGF) blocoGF.style.display = gf ? '' : 'none';
        if (blocoSuporteTela) blocoSuporteTela.style.display = suporteTela ? '' : 'none';
        if (blocoMotivoPessoa) blocoMotivoPessoa.style.display = motivoPessoa ? '' : 'none';
        if (blocoCampoPessoa) blocoCampoPessoa.style.display = campoPessoaVisivel ? '' : 'none';
        if (blocoCampoGF) blocoCampoGF.style.display = campoGFVisivel ? '' : 'none';

        if (motivoTipo) {
            const motivo = motivoTipo.value;
            if (blocoMotivoDetalhe) blocoMotivoDetalhe.style.display = ['mudanca_igreja', 'transferencia_abba'].includes(motivo) ? '' : 'none';
            if (blocoMotivoTexto) blocoMotivoTexto.style.display = motivo === 'motivos_pessoais' ? '' : 'none';
        }

        sincronizarCampoAlteracao();
    }

    if (destino) destino.addEventListener('change', atualizarFormularioChamado);
    if (assunto) assunto.addEventListener('change', atualizarFormularioChamado);
    if (motivoTipo) motivoTipo.addEventListener('change', atualizarFormularioChamado);
    if (campoPessoa) campoPessoa.addEventListener('change', sincronizarCampoAlteracao);
    if (campoGF) campoGF.addEventListener('change', sincronizarCampoAlteracao);

    atualizarFormularioChamado();

    document.querySelectorAll('.btn-toggle-chamado').forEach(function(botao) {
        botao.addEventListener('click', function() {
            const tr = this.closest('.linha-chamado');
            const detalheId = tr ? tr.dataset.detalhe : '';
            if (!detalheId) return;
            const detalhe = document.getElementById(detalheId);
            if (!detalhe) return;
            detalhe.style.display = detalhe.style.display === 'none' ? '' : 'none';
        });
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
