<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../src/Repositories/PresencaRepository.php';
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Services/AuditoriaService.php';

Auth::requireLogin();
Auth::requireSenhaAtualizada();

function dataRetiroValida(string $data): bool
{
    $dt = DateTime::createFromFormat('!Y-m-d', $data);
    return $dt !== false && $dt->format('Y-m-d') === $data && $dt <= new DateTime('today');
}

$repo = new PresencaRepository();
$auditoria = new AuditoriaService();

$usuarioId = Auth::id();
$isAdmin = Auth::isAdmin();

$gruposIntegracao = $isAdmin
    ? $repo->listarGruposIntegracao()
    : $repo->listarGruposIntegracaoPorLider($usuarioId);

if (count($gruposIntegracao) === 0) {
    http_response_code(403);
    die('Você não tem acesso ao Retiro de Integração.');
}

$mensagem = '';
$erro = '';

$grupoId = (int) ($_GET['grupo_id'] ?? $_POST['grupo_id'] ?? 0);
$pessoaId = (int) ($_GET['pessoa_id'] ?? $_POST['pessoa_id'] ?? 0);
$retiroId = (int) ($_GET['editar'] ?? $_POST['retiro_id'] ?? 0);
$dataRetiro = trim($_POST['data_retiro'] ?? '');
$aulasSelecionadas = $_POST['aulas'] ?? [];
$retiroEdicao = null;

if ($retiroId > 0) {
    $retiroEdicao = $repo->buscarRetiroIntegracao($retiroId);
    if (!$retiroEdicao) {
        $erro = 'Retiro de integração não encontrado.';
        $retiroId = 0;
    } else {
        if (!$isAdmin && !$repo->liderPodeAcessarGrupo($usuarioId, (int) $retiroEdicao['grupo_familiar_id'])) {
            http_response_code(403);
            die('Acesso negado a este retiro de integração.');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $grupoId = (int) $retiroEdicao['grupo_familiar_id'];
            $pessoaId = (int) $retiroEdicao['pessoa_id'];
            $dataRetiro = (string) ($retiroEdicao['data_retiro'] ?? '');
            $aulasSelecionadas = $retiroEdicao['aulas_codigos'] ?? [];
        }
    }
}

if ($grupoId > 0 && !$isAdmin && !$repo->liderPodeAcessarGrupo($usuarioId, $grupoId)) {
    http_response_code(403);
    die('Acesso negado a este GF.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_retiro_integracao'])) {
    try {
        if ($grupoId <= 0) {
            throw new InvalidArgumentException('Selecione o GF de integração.');
        }
        if ($pessoaId <= 0) {
            throw new InvalidArgumentException('Selecione o membro que participou do retiro.');
        }
        if (!dataRetiroValida($dataRetiro)) {
            throw new InvalidArgumentException('Informe uma data de retiro válida.');
        }

        if ($retiroId > 0) {
            $repo->atualizarRetiroIntegracao($retiroId, $grupoId, $pessoaId, $dataRetiro, $aulasSelecionadas);
            $mensagem = 'Retiro de integração atualizado com sucesso.';
            $auditoria->registrar(
                'atualizar',
                'retiro_integracao',
                $retiroId,
                'Retiro de integração atualizado.',
                null,
                $grupoId,
                $dataRetiro
            );
        } else {
            $novoRetiroId = $repo->salvarRetiroIntegracao($grupoId, $pessoaId, $dataRetiro, $aulasSelecionadas);
            $mensagem = 'Retiro de integração registrado com sucesso.';
            $auditoria->registrar(
                'criar',
                'retiro_integracao',
                $novoRetiroId,
                'Retiro de integração registrado.',
                null,
                $grupoId,
                $dataRetiro
            );
            $retiroId = 0;
            $dataRetiro = '';
            $aulasSelecionadas = [];
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

if ($retiroId > 0 && $erro === '') {
    $retiroEdicao = $repo->buscarRetiroIntegracao($retiroId);
    if ($retiroEdicao) {
        $grupoId = (int) $retiroEdicao['grupo_familiar_id'];
        $pessoaId = (int) $retiroEdicao['pessoa_id'];
        $dataRetiro = (string) ($retiroEdicao['data_retiro'] ?? '');
        $aulasSelecionadas = $retiroEdicao['aulas_codigos'] ?? [];
    }
}

$membrosGrupo = $grupoId > 0 ? $repo->listarMembrosIntegracaoPorGrupo($grupoId) : [];
$aulasDisponiveis = $pessoaId > 0 ? $repo->listarAulasDisponiveisParaRetiro($pessoaId, $retiroId > 0 ? $retiroId : null) : [];
$retiros = $repo->listarRetirosIntegracao($isAdmin ? null : $usuarioId);

$pageTitle = 'Retiro de Integração - JTRO';

require_once __DIR__ . '/../src/Views/retiro_integracao/index.php';
