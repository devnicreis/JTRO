<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/ChamadoRepository.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';
require_once __DIR__ . '/../src/Views/helpers.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

function limitarTextoChamado(string $texto, int $limite): string
{
    return trim(mb_substr($texto, 0, $limite));
}

function carregarDadosChamado(array $fonte): array
{
    return [
        'destino' => trim((string) ($fonte['destino'] ?? '')),
        'assunto_tipo' => trim((string) ($fonte['assunto_tipo'] ?? '')),
        'tela_problema' => trim((string) ($fonte['tela_problema'] ?? '')),
        'pessoa_id' => (int) ($fonte['pessoa_id'] ?? 0),
        'grupo_familiar_id' => (int) ($fonte['grupo_familiar_id'] ?? 0),
        'campo_alteracao' => trim((string) ($fonte['campo_alteracao'] ?? '')),
        'motivo_desativacao_tipo' => trim((string) ($fonte['motivo_desativacao_tipo'] ?? '')),
        'motivo_desativacao_detalhe' => limitarTextoChamado((string) ($fonte['motivo_desativacao_detalhe'] ?? ''), 100),
        'motivo_desativacao_texto' => limitarTextoChamado((string) ($fonte['motivo_desativacao_texto'] ?? ''), 250),
        'resumo_solicitacao' => limitarTextoChamado((string) ($fonte['resumo_solicitacao'] ?? ''), 1000),
    ];
}

function validarChamadoLider(array $dados, array $pessoasPermitidas, array $gruposPermitidos): array
{
    $destinos = opcoesDestinoChamado();
    $assuntosSecretaria = opcoesAssuntoSecretaria();
    $assuntosSuporte = opcoesAssuntoSuporte();
    $camposPessoa = opcoesCamposPessoaChamado();
    $camposGF = opcoesCamposGFChamado();
    $motivosPessoa = opcoesMotivoDesativacaoPessoa();
    $telasSuporte = opcoesTelasSuporte();

    if (!isset($destinos[$dados['destino']])) {
        throw new InvalidArgumentException('Selecione o destino da solicitação.');
    }

    $assuntoLabel = '';
    if ($dados['destino'] === 'secretaria') {
        if (!isset($assuntosSecretaria[$dados['assunto_tipo']])) {
            throw new InvalidArgumentException('Selecione o assunto da solicitação.');
        }
        $assuntoLabel = $assuntosSecretaria[$dados['assunto_tipo']];

        if (in_array($dados['assunto_tipo'], ['edicao_pessoa', 'desativacao_pessoa'], true)) {
            if ($dados['pessoa_id'] <= 0 || !isset($pessoasPermitidas[$dados['pessoa_id']])) {
                throw new InvalidArgumentException('Selecione uma pessoa válida.');
            }
        }

        if (in_array($dados['assunto_tipo'], ['edicao_gf', 'desativacao_gf'], true)) {
            if ($dados['grupo_familiar_id'] <= 0 || !isset($gruposPermitidos[$dados['grupo_familiar_id']])) {
                throw new InvalidArgumentException('Selecione um GF válido.');
            }
        }

        if ($dados['assunto_tipo'] === 'edicao_pessoa' && !isset($camposPessoa[$dados['campo_alteracao']])) {
            throw new InvalidArgumentException('Selecione o campo da pessoa que precisa ser alterado.');
        }

        if ($dados['assunto_tipo'] === 'edicao_gf' && !isset($camposGF[$dados['campo_alteracao']])) {
            throw new InvalidArgumentException('Selecione o campo do GF que precisa ser alterado.');
        }

        if ($dados['assunto_tipo'] === 'desativacao_pessoa') {
            if (!isset($motivosPessoa[$dados['motivo_desativacao_tipo']])) {
                throw new InvalidArgumentException('Selecione o motivo da desativação da pessoa.');
            }
            if (in_array($dados['motivo_desativacao_tipo'], ['mudanca_igreja', 'transferencia_abba'], true)
                && $dados['motivo_desativacao_detalhe'] === '') {
                throw new InvalidArgumentException('Informe para qual igreja ou unidade a pessoa irá.');
            }
            if ($dados['motivo_desativacao_tipo'] === 'motivos_pessoais'
                && $dados['motivo_desativacao_texto'] === '') {
                throw new InvalidArgumentException('Resuma os motivos pessoais da desativação.');
            }
        }

        if ($dados['assunto_tipo'] === 'desativacao_gf' && $dados['resumo_solicitacao'] === '') {
            throw new InvalidArgumentException('Informe o motivo da desativação do GF.');
        }

        if ($dados['assunto_tipo'] === 'outros' && $dados['resumo_solicitacao'] === '') {
            throw new InvalidArgumentException('Resuma sua solicitação.');
        }
    } else {
        if (!isset($assuntosSuporte[$dados['assunto_tipo']])) {
            throw new InvalidArgumentException('Selecione o assunto do suporte.');
        }
        $assuntoLabel = $assuntosSuporte[$dados['assunto_tipo']];

        if ($dados['assunto_tipo'] === 'problema_tela' && !isset($telasSuporte[$dados['tela_problema']])) {
            throw new InvalidArgumentException('Selecione a tela com problema.');
        }

        if ($dados['resumo_solicitacao'] === '') {
            throw new InvalidArgumentException('Resuma sua solicitação.');
        }
    }

    return [
        'assunto_label' => $assuntoLabel,
        'destino_label' => $destinos[$dados['destino']],
        'campo_alteracao_label' => $camposPessoa[$dados['campo_alteracao']] ?? $camposGF[$dados['campo_alteracao']] ?? '',
        'motivo_desativacao_label' => $motivosPessoa[$dados['motivo_desativacao_tipo']] ?? '',
        'tela_problema_label' => $telasSuporte[$dados['tela_problema']] ?? '',
        'pessoa_label' => $pessoasPermitidas[$dados['pessoa_id']]['nome'] ?? '',
        'grupo_label' => $gruposPermitidos[$dados['grupo_familiar_id']]['nome'] ?? '',
    ];
}

$repo = new ChamadoRepository();
$pessoaRepo = new PessoaRepository();
$auditoria = new AuditoriaService();

$usuarioId = Auth::id();
$isAdmin = Auth::isAdmin();
$usuarioCompleto = $pessoaRepo->buscarPorId($usuarioId);

$mensagem = '';
$erro = '';
$previewChamado = null;
$numeroChamadoCriado = null;

$pessoasPermitidasLista = $repo->listarPessoasDosGruposDoLider($usuarioId);
$gruposPermitidosLista = $repo->listarGruposDoLider($usuarioId);
$pessoasPermitidas = [];
$gruposPermitidos = [];

foreach ($pessoasPermitidasLista as $pessoa) {
    $pessoasPermitidas[(int) $pessoa['id']] = $pessoa;
}
foreach ($gruposPermitidosLista as $grupo) {
    $gruposPermitidos[(int) $grupo['id']] = $grupo;
}

$formChamado = carregarDadosChamado($_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && ($_POST['acao'] ?? '') === 'atualizar_status') {
    $chamadoId = (int) ($_POST['chamado_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));
    $observacaoAdmin = limitarTextoChamado((string) ($_POST['observacao_admin'] ?? ''), 500);

    try {
        $repo->atualizarStatus($chamadoId, $status, $observacaoAdmin, $usuarioId);
        $mensagem = 'Chamado atualizado com sucesso.';
        $auditoria->registrar('atualizar', 'chamado', $chamadoId, 'Status de chamado atualizado.', $usuarioId, null, null);
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAdmin) {
    $acao = $_POST['acao'] ?? '';

    if (in_array($acao, ['pre_visualizar', 'abrir_chamado'], true)) {
        try {
            $validacao = validarChamadoLider($formChamado, $pessoasPermitidas, $gruposPermitidos);
            $previewChamado = array_merge($formChamado, $validacao);
            $previewChamado['nome_solicitante'] = $usuarioCompleto['nome'] ?? 'Líder';
            $previewChamado['contato_solicitante'] = formatarTelefone($usuarioCompleto['telefone_movel'] ?? ($usuarioCompleto['telefone_fixo'] ?? null));

            if ($acao === 'abrir_chamado') {
                $numeroChamadoCriado = $repo->criarChamado([
                    'solicitante_id' => $usuarioId,
                    'destino' => $previewChamado['destino'],
                    'assunto_tipo' => $previewChamado['assunto_tipo'],
                    'assunto_label' => $previewChamado['assunto_label'],
                    'tela_problema' => $previewChamado['tela_problema'],
                    'pessoa_id' => $previewChamado['pessoa_id'],
                    'grupo_familiar_id' => $previewChamado['grupo_familiar_id'],
                    'campo_alteracao' => $previewChamado['campo_alteracao'],
                    'motivo_desativacao_tipo' => $previewChamado['motivo_desativacao_tipo'],
                    'motivo_desativacao_detalhe' => $previewChamado['motivo_desativacao_detalhe'],
                    'motivo_desativacao_texto' => $previewChamado['motivo_desativacao_texto'],
                    'resumo_solicitacao' => $previewChamado['resumo_solicitacao'],
                ]);

                $mensagem = 'Sua solicitação foi encaminhada a ' . $previewChamado['destino_label'] . ' com sucesso!';
                $auditoria->registrar('criar', 'chamado', null, 'Chamado ' . $numeroChamadoCriado . ' aberto.', $usuarioId, null, null);
                $previewChamado = null;
                $formChamado = carregarDadosChamado([]);
            }
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

$meusChamados = !$isAdmin ? $repo->listarChamadosDoSolicitante($usuarioId) : [];
$chamadosAdmin = $isAdmin ? $repo->listarChamadosParaAdmin() : [];

$pageTitle = 'Chamados - JTRO';
require_once __DIR__ . '/../src/Views/chamados/index.php';
