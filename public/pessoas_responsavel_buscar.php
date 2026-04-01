<?php

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/PessoaRepository.php';

Auth::requireAdmin();
Auth::requireSenhaAtualizada();

header('Content-Type: application/json; charset=utf-8');

$cpf = preg_replace('/\D+/', '', (string) ($_GET['cpf'] ?? ''));
$ignorarId = (int) ($_GET['ignorar_id'] ?? 0);

if ($cpf === '' || strlen($cpf) !== 11) {
    http_response_code(400);
    echo json_encode(['encontrado' => false, 'erro' => 'CPF invalido.']);
    exit;
}

$repo = new PessoaRepository();
$responsavel = $repo->buscarResponsavelPorCpf($cpf, $ignorarId > 0 ? $ignorarId : null);

if ($responsavel === null) {
    echo json_encode(['encontrado' => false]);
    exit;
}

echo json_encode([
    'encontrado' => true,
    'pessoa_id' => (int) ($responsavel['id'] ?? 0),
    'nome' => (string) ($responsavel['nome'] ?? ''),
]);
