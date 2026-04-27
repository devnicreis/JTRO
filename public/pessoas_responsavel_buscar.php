<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

header('Content-Type: application/json; charset=utf-8');

$cpf = preg_replace('/\D+/', '', (string) ($_GET['cpf'] ?? ''));
$nome = trim((string) ($_GET['nome'] ?? ''));
$ignorarId = (int) ($_GET['ignorar_id'] ?? 0);

$repo = new PessoaRepository();

if ($cpf !== '') {
    if (strlen($cpf) !== 11) {
        http_response_code(400);
        echo json_encode(['encontrado' => false, 'erro' => 'CPF invalido.']);
        exit;
    }

    $responsavel = $repo->buscarResponsavelPorCpf($cpf, $ignorarId > 0 ? $ignorarId : null);

    if ($responsavel === null) {
        echo json_encode(['encontrado' => false]);
        exit;
    }

    echo json_encode([
        'encontrado' => true,
        'pessoa_id' => (int) ($responsavel['id'] ?? 0),
        'cpf' => (string) ($responsavel['cpf'] ?? ''),
        'nome' => (string) ($responsavel['nome'] ?? ''),
        'email' => (string) ($responsavel['email'] ?? ''),
        'telefone_fixo' => (string) ($responsavel['telefone_fixo'] ?? ''),
        'telefone_movel' => (string) ($responsavel['telefone_movel'] ?? ''),
        'endereco_cep' => (string) ($responsavel['endereco_cep'] ?? ''),
        'endereco_logradouro' => (string) ($responsavel['endereco_logradouro'] ?? ''),
        'endereco_numero' => (string) ($responsavel['endereco_numero'] ?? ''),
        'endereco_complemento' => (string) ($responsavel['endereco_complemento'] ?? ''),
        'endereco_bairro' => (string) ($responsavel['endereco_bairro'] ?? ''),
        'endereco_cidade' => (string) ($responsavel['endereco_cidade'] ?? ''),
        'endereco_uf' => (string) ($responsavel['endereco_uf'] ?? ''),
    ]);
    exit;
}

if ($nome !== '') {
    if (mb_strlen($nome) < 2) {
        echo json_encode(['resultados' => []]);
        exit;
    }

    $resultados = $repo->buscarResponsaveisPorNome($nome, $ignorarId > 0 ? $ignorarId : null, 12);
    echo json_encode([
        'resultados' => array_map(static function (array $pessoa): array {
            return [
                'pessoa_id' => (int) ($pessoa['id'] ?? 0),
                'cpf' => (string) ($pessoa['cpf'] ?? ''),
                'nome' => (string) ($pessoa['nome'] ?? ''),
            ];
        }, $resultados),
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['erro' => 'Informe cpf ou nome para busca.']);
