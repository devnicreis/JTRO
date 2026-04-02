<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Pessoa.php';

class PessoaRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function salvar(Pessoa $pessoa, array $dados = []): int
    {
        $this->connection->beginTransaction();

        try {
            $sql = "INSERT INTO pessoas (
                        nome, cpf, cargo, genero, ativo, email, data_nascimento, estado_civil, nome_conjuge,
                        conjuge_cpf, conjuge_pessoa_id,
                        eh_lider, lider_grupo_familiar, lider_departamento, telefone_fixo, telefone_movel,
                        endereco_cep, endereco_logradouro, endereco_numero, endereco_complemento,
                        endereco_bairro, endereco_cidade, endereco_uf,
                        concluiu_integracao, integracao_conclusao_manual, participou_retiro_integracao,
                        responsavel_1_cpf, responsavel_1_nome, responsavel_1_pessoa_id,
                        responsavel_2_cpf, responsavel_2_nome, responsavel_2_pessoa_id
                    ) VALUES (
                        :nome, :cpf, :cargo, :genero, :ativo, :email, :data_nascimento, :estado_civil, :nome_conjuge,
                        :conjuge_cpf, :conjuge_pessoa_id,
                        :eh_lider, :lider_grupo_familiar, :lider_departamento, :telefone_fixo, :telefone_movel,
                        :endereco_cep, :endereco_logradouro, :endereco_numero, :endereco_complemento,
                        :endereco_bairro, :endereco_cidade, :endereco_uf,
                        :concluiu_integracao, :integracao_conclusao_manual, :participou_retiro_integracao,
                        :responsavel_1_cpf, :responsavel_1_nome, :responsavel_1_pessoa_id,
                        :responsavel_2_cpf, :responsavel_2_nome, :responsavel_2_pessoa_id
                    )";

            $stmt = $this->connection->prepare($sql);

            $stmt->execute([
                ':nome' => $pessoa->nome,
                ':cpf' => $pessoa->getCpf(),
                ':cargo' => $pessoa->getCargo(),
                ':genero' => $this->normalizarTextoOpcional($dados['genero'] ?? null),
                ':ativo' => $pessoa->ativo ? 1 : 0,
                ':email' => $this->normalizarTextoOpcional($dados['email'] ?? null),
                ':data_nascimento' => $this->normalizarTextoOpcional($dados['data_nascimento'] ?? null),
                ':estado_civil' => $dados['estado_civil'] ?? 'solteiro',
                ':nome_conjuge' => $this->normalizarTextoOpcional($dados['nome_conjuge'] ?? null),
                ':conjuge_cpf' => $this->normalizarTextoOpcional($dados['conjuge_cpf'] ?? null),
                ':conjuge_pessoa_id' => $this->normalizarInteiroOpcional($dados['conjuge_pessoa_id'] ?? null),
                ':eh_lider' => !empty($dados['eh_lider']) ? 1 : 0,
                ':lider_grupo_familiar' => !empty($dados['lider_grupo_familiar']) ? 1 : 0,
                ':lider_departamento' => !empty($dados['lider_departamento']) ? 1 : 0,
                ':telefone_fixo' => $this->normalizarTextoOpcional($dados['telefone_fixo'] ?? null),
                ':telefone_movel' => $this->normalizarTextoOpcional($dados['telefone_movel'] ?? null),
                ':endereco_cep' => $this->normalizarTextoOpcional($dados['endereco_cep'] ?? null),
                ':endereco_logradouro' => $this->normalizarTextoOpcional($dados['endereco_logradouro'] ?? null),
                ':endereco_numero' => $this->normalizarTextoOpcional($dados['endereco_numero'] ?? null),
                ':endereco_complemento' => $this->normalizarTextoOpcional($dados['endereco_complemento'] ?? null),
                ':endereco_bairro' => $this->normalizarTextoOpcional($dados['endereco_bairro'] ?? null),
                ':endereco_cidade' => $this->normalizarTextoOpcional($dados['endereco_cidade'] ?? null),
                ':endereco_uf' => $this->normalizarTextoOpcional($dados['endereco_uf'] ?? null),
                ':concluiu_integracao' => !empty($dados['concluiu_integracao']) ? 1 : 0,
                ':integracao_conclusao_manual' => array_key_exists('integracao_conclusao_manual', $dados)
                    ? (!empty($dados['integracao_conclusao_manual']) ? 1 : 0)
                    : (!empty($dados['concluiu_integracao']) ? 1 : 0),
                ':participou_retiro_integracao' => !empty($dados['participou_retiro_integracao']) ? 1 : 0,
                ':responsavel_1_cpf' => $this->normalizarTextoOpcional($dados['responsavel_1_cpf'] ?? null),
                ':responsavel_1_nome' => $this->normalizarTextoOpcional($dados['responsavel_1_nome'] ?? null),
                ':responsavel_1_pessoa_id' => $this->normalizarInteiroOpcional($dados['responsavel_1_pessoa_id'] ?? null),
                ':responsavel_2_cpf' => $this->normalizarTextoOpcional($dados['responsavel_2_cpf'] ?? null),
                ':responsavel_2_nome' => $this->normalizarTextoOpcional($dados['responsavel_2_nome'] ?? null),
                ':responsavel_2_pessoa_id' => $this->normalizarInteiroOpcional($dados['responsavel_2_pessoa_id'] ?? null),
            ]);

            $pessoaId = (int) $this->connection->lastInsertId();
            $this->atualizarGrupoPrincipalInterno($pessoaId, $this->normalizarInteiroOpcional($dados['grupo_familiar_id'] ?? null));

            $this->connection->commit();

            return $pessoaId;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function listarTodos(array $filtros = []): array
    {
        $grupoFamiliarExibicaoIdSql = $this->grupoFamiliarExibicaoIdSql('p');
        $grupoFamiliarExibicaoNomeSql = $this->grupoFamiliarExibicaoNomeSql($grupoFamiliarExibicaoIdSql);
        $grupoFamiliarFiltro = ($filtros['grupo_familiar_id'] ?? '') !== ''
            ? (int) $filtros['grupo_familiar_id']
            : null;
        $sql = "
            SELECT
                p.*,
                COALESCE({$grupoFamiliarExibicaoIdSql}, gf_principal.id) AS grupo_familiar_exibicao_id,
                COALESCE({$grupoFamiliarExibicaoNomeSql}, gf_principal.nome) AS grupo_familiar_nome
            FROM pessoas p
            LEFT JOIN grupos_familiares gf_principal ON gf_principal.id = p.grupo_familiar_id
        ";

        $where = [];
        $params = [];

        if (($filtros['id'] ?? '') !== '') {
            $where[] = 'p.id = :id';
            $params[':id'] = (int) $filtros['id'];
        }

        if (($filtros['nome'] ?? '') !== '') {
            $where[] = 'LOWER(p.nome) LIKE :nome';
            $params[':nome'] = '%' . mb_strtolower(trim($filtros['nome'])) . '%';
        }

        if (($filtros['cpf'] ?? '') !== '') {
            $where[] = 'p.cpf LIKE :cpf';
            $params[':cpf'] = '%' . preg_replace('/\D+/', '', (string) $filtros['cpf']) . '%';
        }

        if (($filtros['email'] ?? '') !== '') {
            $where[] = 'LOWER(COALESCE(p.email, \'\')) LIKE :email';
            $params[':email'] = '%' . mb_strtolower(trim($filtros['email'])) . '%';
        }

        if (($filtros['cargo'] ?? '') !== '') {
            $where[] = 'p.cargo = :cargo';
            $params[':cargo'] = $filtros['cargo'];
        }

        if (($filtros['genero'] ?? '') !== '') {
            $where[] = 'p.genero = :genero';
            $params[':genero'] = $filtros['genero'];
        }

        if (($filtros['status'] ?? '') !== '') {
            $where[] = 'p.ativo = :ativo';
            $params[':ativo'] = (int) $filtros['status'];
        }

        if (($filtros['data_nascimento'] ?? '') !== '') {
            $where[] = 'p.data_nascimento = :data_nascimento';
            $params[':data_nascimento'] = $filtros['data_nascimento'];
        }

        if (($filtros['telefone'] ?? '') !== '') {
            $where[] = "(COALESCE(p.telefone_fixo, '') LIKE :telefone OR COALESCE(p.telefone_movel, '') LIKE :telefone)";
            $params[':telefone'] = '%' . preg_replace('/\D+/', '', (string) $filtros['telefone']) . '%';
        }

        if (($filtros['telefone_fixo'] ?? '') !== '') {
            $where[] = "COALESCE(p.telefone_fixo, '') LIKE :telefone_fixo";
            $params[':telefone_fixo'] = '%' . preg_replace('/\D+/', '', (string) $filtros['telefone_fixo']) . '%';
        }

        if (($filtros['telefone_movel'] ?? '') !== '') {
            $where[] = "COALESCE(p.telefone_movel, '') LIKE :telefone_movel";
            $params[':telefone_movel'] = '%' . preg_replace('/\D+/', '', (string) $filtros['telefone_movel']) . '%';
        }

        if (($filtros['contato'] ?? '') !== '') {
            $where[] = "LOWER(COALESCE(p.email, '')) LIKE :contato";
            $params[':contato'] = '%' . mb_strtolower(trim((string) $filtros['contato'])) . '%';
        }

        if (($filtros['endereco'] ?? '') !== '') {
            $where[] = "LOWER(
                COALESCE(p.endereco_logradouro, '') || ' ' ||
                COALESCE(p.endereco_numero, '') || ' ' ||
                COALESCE(p.endereco_complemento, '') || ' ' ||
                COALESCE(p.endereco_bairro, '') || ' ' ||
                COALESCE(p.endereco_cidade, '') || ' ' ||
                COALESCE(p.endereco_uf, '') || ' ' ||
                COALESCE(p.endereco_cep, '')
            ) LIKE :endereco";
            $params[':endereco'] = '%' . mb_strtolower(trim((string) $filtros['endereco'])) . '%';
        }

        if (($filtros['estado_civil'] ?? '') !== '') {
            $where[] = 'p.estado_civil = :estado_civil';
            $params[':estado_civil'] = $filtros['estado_civil'];
        }

        if (($filtros['nome_conjuge'] ?? '') !== '') {
            $where[] = "LOWER(COALESCE(p.nome_conjuge, '')) LIKE :nome_conjuge";
            $params[':nome_conjuge'] = '%' . mb_strtolower(trim((string) $filtros['nome_conjuge'])) . '%';
        }

        if (($filtros['eh_lider'] ?? '') !== '') {
            $where[] = 'p.eh_lider = :eh_lider';
            $params[':eh_lider'] = (int) $filtros['eh_lider'];
        }

        if (($filtros['lider_grupo_familiar'] ?? '') !== '') {
            $where[] = 'p.lider_grupo_familiar = :lider_grupo_familiar';
            $params[':lider_grupo_familiar'] = (int) $filtros['lider_grupo_familiar'];
        }

        if (($filtros['lider_departamento'] ?? '') !== '') {
            $where[] = 'p.lider_departamento = :lider_departamento';
            $params[':lider_departamento'] = (int) $filtros['lider_departamento'];
        }

        if (($filtros['lideranca'] ?? '') !== '') {
            if ($filtros['lideranca'] === 'gf') {
                $where[] = 'p.lider_grupo_familiar = 1';
            } elseif ($filtros['lideranca'] === 'dpto') {
                $where[] = 'p.lider_departamento = 1';
            } elseif ($filtros['lideranca'] === 'gf_e_dpto') {
                $where[] = '(p.lider_grupo_familiar = 1 AND p.lider_departamento = 1)';
            } elseif ($filtros['lideranca'] === 'gf_ou_dpto') {
                $where[] = '(p.lider_grupo_familiar = 1 OR p.lider_departamento = 1)';
            } elseif ($filtros['lideranca'] === 'nao') {
                $where[] = '(p.lider_grupo_familiar = 0 AND p.lider_departamento = 0)';
            }
        }

        if (($filtros['concluiu_integracao'] ?? '') !== '') {
            $where[] = 'p.concluiu_integracao = :concluiu_integracao';
            $params[':concluiu_integracao'] = (int) $filtros['concluiu_integracao'];
        }

        if (($filtros['participou_retiro_integracao'] ?? '') !== '') {
            $where[] = 'p.participou_retiro_integracao = :participou_retiro_integracao';
            $params[':participou_retiro_integracao'] = (int) $filtros['participou_retiro_integracao'];
        }

        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.id DESC';

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($grupoFamiliarFiltro !== null) {
            $linhas = array_values(array_filter($linhas, static function (array $linha) use ($grupoFamiliarFiltro): bool {
                return (int) ($linha['grupo_familiar_exibicao_id'] ?? 0) === $grupoFamiliarFiltro;
            }));
        }

        return $linhas;
    }

    public function listarAtivas(): array
    {
        $sql = "SELECT * FROM pessoas WHERE ativo = 1 ORDER BY nome ASC";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorCpf(string $cpf): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE cpf = :cpf LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':cpf' => $cpf]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $grupoFamiliarExibicaoIdSql = $this->grupoFamiliarExibicaoIdSql('p');
        $grupoFamiliarExibicaoNomeSql = $this->grupoFamiliarExibicaoNomeSql($grupoFamiliarExibicaoIdSql);
        $sql = "
            SELECT
                p.*,
                COALESCE({$grupoFamiliarExibicaoIdSql}, gf_principal.id) AS grupo_familiar_exibicao_id,
                COALESCE({$grupoFamiliarExibicaoNomeSql}, gf_principal.nome) AS grupo_familiar_nome
            FROM pessoas p
            LEFT JOIN grupos_familiares gf_principal ON gf_principal.id = p.grupo_familiar_id
            WHERE p.id = :id
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarPorCpfExcetoId(string $cpf, int $id): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE cpf = :cpf AND id != :id LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':cpf' => $cpf,
            ':id' => $id
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function atualizar(int $id, array $dados): void
    {
        $this->connection->beginTransaction();

        try {
            $sql = "
                UPDATE pessoas
                SET nome = :nome,
                    cpf = :cpf,
                    cargo = :cargo,
                    genero = :genero,
                    email = :email,
                    data_nascimento = :data_nascimento,
                    estado_civil = :estado_civil,
                    nome_conjuge = :nome_conjuge,
                    conjuge_cpf = :conjuge_cpf,
                    conjuge_pessoa_id = :conjuge_pessoa_id,
                    eh_lider = :eh_lider,
                    lider_grupo_familiar = :lider_grupo_familiar,
                    lider_departamento = :lider_departamento,
                    telefone_fixo = :telefone_fixo,
                    telefone_movel = :telefone_movel,
                    endereco_cep = :endereco_cep,
                    endereco_logradouro = :endereco_logradouro,
                    endereco_numero = :endereco_numero,
                    endereco_complemento = :endereco_complemento,
                    endereco_bairro = :endereco_bairro,
                    endereco_cidade = :endereco_cidade,
                    endereco_uf = :endereco_uf,
                    concluiu_integracao = :concluiu_integracao,
                    integracao_conclusao_manual = :integracao_conclusao_manual,
                    participou_retiro_integracao = :participou_retiro_integracao,
                    responsavel_1_cpf = :responsavel_1_cpf,
                    responsavel_1_nome = :responsavel_1_nome,
                    responsavel_1_pessoa_id = :responsavel_1_pessoa_id,
                    responsavel_2_cpf = :responsavel_2_cpf,
                    responsavel_2_nome = :responsavel_2_nome,
                    responsavel_2_pessoa_id = :responsavel_2_pessoa_id
                WHERE id = :id
            ";

            $stmt = $this->connection->prepare($sql);

            $stmt->execute([
                ':nome' => $dados['nome'],
                ':cpf' => $dados['cpf'],
                ':cargo' => $dados['cargo'],
                ':genero' => $this->normalizarTextoOpcional($dados['genero'] ?? null),
                ':email' => $this->normalizarTextoOpcional($dados['email'] ?? null),
                ':data_nascimento' => $this->normalizarTextoOpcional($dados['data_nascimento'] ?? null),
                ':estado_civil' => $dados['estado_civil'] ?? 'solteiro',
                ':nome_conjuge' => $this->normalizarTextoOpcional($dados['nome_conjuge'] ?? null),
                ':conjuge_cpf' => $this->normalizarTextoOpcional($dados['conjuge_cpf'] ?? null),
                ':conjuge_pessoa_id' => $this->normalizarInteiroOpcional($dados['conjuge_pessoa_id'] ?? null),
                ':eh_lider' => !empty($dados['eh_lider']) ? 1 : 0,
                ':lider_grupo_familiar' => !empty($dados['lider_grupo_familiar']) ? 1 : 0,
                ':lider_departamento' => !empty($dados['lider_departamento']) ? 1 : 0,
                ':telefone_fixo' => $this->normalizarTextoOpcional($dados['telefone_fixo'] ?? null),
                ':telefone_movel' => $this->normalizarTextoOpcional($dados['telefone_movel'] ?? null),
                ':endereco_cep' => $this->normalizarTextoOpcional($dados['endereco_cep'] ?? null),
                ':endereco_logradouro' => $this->normalizarTextoOpcional($dados['endereco_logradouro'] ?? null),
                ':endereco_numero' => $this->normalizarTextoOpcional($dados['endereco_numero'] ?? null),
                ':endereco_complemento' => $this->normalizarTextoOpcional($dados['endereco_complemento'] ?? null),
                ':endereco_bairro' => $this->normalizarTextoOpcional($dados['endereco_bairro'] ?? null),
                ':endereco_cidade' => $this->normalizarTextoOpcional($dados['endereco_cidade'] ?? null),
                ':endereco_uf' => $this->normalizarTextoOpcional($dados['endereco_uf'] ?? null),
                ':concluiu_integracao' => !empty($dados['concluiu_integracao']) ? 1 : 0,
                ':integracao_conclusao_manual' => array_key_exists('integracao_conclusao_manual', $dados)
                    ? (!empty($dados['integracao_conclusao_manual']) ? 1 : 0)
                    : (!empty($dados['concluiu_integracao']) ? 1 : 0),
                ':participou_retiro_integracao' => !empty($dados['participou_retiro_integracao']) ? 1 : 0,
                ':responsavel_1_cpf' => $this->normalizarTextoOpcional($dados['responsavel_1_cpf'] ?? null),
                ':responsavel_1_nome' => $this->normalizarTextoOpcional($dados['responsavel_1_nome'] ?? null),
                ':responsavel_1_pessoa_id' => $this->normalizarInteiroOpcional($dados['responsavel_1_pessoa_id'] ?? null),
                ':responsavel_2_cpf' => $this->normalizarTextoOpcional($dados['responsavel_2_cpf'] ?? null),
                ':responsavel_2_nome' => $this->normalizarTextoOpcional($dados['responsavel_2_nome'] ?? null),
                ':responsavel_2_pessoa_id' => $this->normalizarInteiroOpcional($dados['responsavel_2_pessoa_id'] ?? null),
                ':id' => $id
            ]);

            $this->atualizarGrupoPrincipalInterno($id, $this->normalizarInteiroOpcional($dados['grupo_familiar_id'] ?? null));

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function desativar(int $id, array $motivo): void
    {
        $sql = "
            UPDATE pessoas
            SET ativo = 0,
                motivo_desativacao_tipo = :tipo,
                motivo_desativacao_detalhe = :detalhe,
                motivo_desativacao_texto = :texto
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':tipo' => $this->normalizarTextoOpcional($motivo['tipo'] ?? null),
            ':detalhe' => $this->normalizarTextoOpcional($motivo['detalhe'] ?? null),
            ':texto' => $this->normalizarTextoOpcional($motivo['texto'] ?? null),
            ':id' => $id
        ]);
    }

    public function reativar(int $id): void
    {
        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare("
                UPDATE pessoas
                SET ativo = 1,
                    grupo_familiar_id = NULL,
                    motivo_desativacao_tipo = NULL,
                    motivo_desativacao_detalhe = NULL,
                    motivo_desativacao_texto = NULL
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $stmt = $this->connection->prepare("
                DELETE FROM grupo_membros
                WHERE pessoa_id = :id
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $stmt = $this->connection->prepare("
                DELETE FROM grupo_lideres
                WHERE pessoa_id = :id
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function buscarPorCpfAtivo(string $cpf): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE cpf = :cpf AND ativo = 1 LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':cpf' => $cpf]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function atualizarSenha(int $id, string $senha): void
    {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "UPDATE pessoas SET senha_hash = :senha_hash WHERE id = :id";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':senha_hash' => $senhaHash,
            ':id' => $id
        ]);
    }

    public function definirSenhaNoCadastro(Pessoa $pessoa, string $senha): void
    {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO pessoas (nome, cpf, cargo, ativo, senha_hash)
            VALUES (:nome, :cpf, :cargo, :ativo, :senha_hash)";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':nome' => $pessoa->nome,
            ':cpf' => $pessoa->getCpf(),
            ':cargo' => $pessoa->getCargo(),
            ':ativo' => $pessoa->ativo ? 1 : 0,
            ':senha_hash' => $senhaHash
        ]);
    }

    public function marcarPrecisaTrocarSenha(int $id, bool $valor): void
    {
        $sql = "UPDATE pessoas SET precisa_trocar_senha = :valor WHERE id = :id";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':valor' => $valor ? 1 : 0,
            ':id' => $id
        ]);
    }

    public function atualizarSenhaEObrigacao(int $id, string $senha, bool $precisaTrocarSenha): void
    {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "
        UPDATE pessoas
        SET senha_hash = :senha_hash,
            precisa_trocar_senha = :precisa_trocar_senha
        WHERE id = :id
    ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':senha_hash' => $senhaHash,
            ':precisa_trocar_senha' => $precisaTrocarSenha ? 1 : 0,
            ':id' => $id
        ]);
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE LOWER(COALESCE(email, '')) = :email LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':email' => mb_strtolower(trim($email))]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarResponsavelPorCpf(string $cpf, ?int $ignorarId = null): ?array
    {
        $sql = "SELECT id, nome, cpf, ativo FROM pessoas WHERE cpf = :cpf";
        $params = [':cpf' => $cpf];

        if ($ignorarId !== null && $ignorarId > 0) {
            $sql .= " AND id != :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarPorEmailAtivo(string $email): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE LOWER(COALESCE(email, '')) = :email AND ativo = 1 LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':email' => mb_strtolower(trim($email))]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarPorEmailExcetoId(string $email, int $id): ?array
    {
        $sql = "SELECT * FROM pessoas WHERE LOWER(COALESCE(email, '')) = :email AND id != :id LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':email' => mb_strtolower(trim($email)),
            ':id' => $id
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function contarPessoasAtivas(): int
    {
        $stmt = $this->connection->query("
        SELECT COUNT(*)
        FROM pessoas
        WHERE ativo = 1
    ");

        return (int) $stmt->fetchColumn();
    }

    public function contarLideresAtivos(): int
    {
        $stmt = $this->connection->query("
        SELECT COUNT(DISTINCT gl.pessoa_id)
        FROM grupo_lideres gl
        INNER JOIN pessoas p ON p.id = gl.pessoa_id
        WHERE p.ativo = 1
    ");

        return (int) $stmt->fetchColumn();
    }

    public function buscarResumoPresencaPorGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            SUM(CASE WHEN p.status = 'presente' THEN 1 ELSE 0 END) AS total_presencas,
            SUM(CASE WHEN p.status = 'ausente' THEN 1 ELSE 0 END) AS total_ausencias,
            COUNT(*) AS total_registros
        FROM presencas p
        INNER JOIN reunioes r ON r.id = p.reuniao_id
        WHERE r.grupo_familiar_id = :grupo_id
    ");

        $stmt->execute([':grupo_id' => $grupoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $presencas = (int) ($resultado['total_presencas'] ?? 0);
        $ausencias = (int) ($resultado['total_ausencias'] ?? 0);
        $total = (int) ($resultado['total_registros'] ?? 0);

        return [
            'total_presencas' => $presencas,
            'total_ausencias' => $ausencias,
            'percentual_presencas' => $total > 0 ? round(($presencas / $total) * 100, 1) : 0,
            'percentual_ausencias' => $total > 0 ? round(($ausencias / $total) * 100, 1) : 0,
        ];
    }

    public function buscarResumoPorMembroDoGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            pe.id AS pessoa_id,
            pe.nome,
            SUM(CASE WHEN p.status = 'presente' THEN 1 ELSE 0 END) AS total_presencas,
            SUM(CASE WHEN p.status = 'ausente' THEN 1 ELSE 0 END) AS total_ausencias,
            MAX(CASE WHEN p.status = 'presente' THEN r.data ELSE NULL END) AS ultima_presenca
        FROM grupo_membros gm
        INNER JOIN pessoas pe ON pe.id = gm.pessoa_id
        LEFT JOIN reunioes r ON r.grupo_familiar_id = gm.grupo_familiar_id
        LEFT JOIN presencas p ON p.reuniao_id = r.id AND p.pessoa_id = pe.id
        WHERE gm.grupo_familiar_id = :grupo_id
        AND pe.ativo = 1
        GROUP BY pe.id, pe.nome
        ORDER BY pe.nome ASC
    ");

        $stmt->execute([':grupo_id' => $grupoId]);
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($linhas as &$linha) {
            $presencas = (int) ($linha['total_presencas'] ?? 0);
            $ausencias = (int) ($linha['total_ausencias'] ?? 0);
            $total = $presencas + $ausencias;

            $linha['percentual_presenca'] = $total > 0 ? round(($presencas / $total) * 100, 1) : 0;
        }

        return $linhas;
    }

    public function buscarMembrosComFaltasConsecutivas(int $grupoId, int $minimo = 2): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            pe.id AS pessoa_id,
            pe.nome,
            r.data,
            p.status
        FROM grupo_membros gm
        INNER JOIN pessoas pe ON pe.id = gm.pessoa_id
        INNER JOIN reunioes r ON r.grupo_familiar_id = gm.grupo_familiar_id
        INNER JOIN presencas p ON p.reuniao_id = r.id AND p.pessoa_id = pe.id
        WHERE gm.grupo_familiar_id = :grupo_id
        AND pe.ativo = 1
        ORDER BY pe.id ASC, r.data DESC, r.id DESC
    ");

        $stmt->execute([':grupo_id' => $grupoId]);
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        $controle = [];

        foreach ($linhas as $linha) {
            $pessoaId = (int) $linha['pessoa_id'];

            if (!isset($controle[$pessoaId])) {
                $controle[$pessoaId] = [
                    'nome' => $linha['nome'],
                    'faltas' => 0,
                    'encerrado' => false
                ];
            }

            if ($controle[$pessoaId]['encerrado']) {
                continue;
            }

            if ($linha['status'] === 'ausente') {
                $controle[$pessoaId]['faltas']++;
            } else {
                $controle[$pessoaId]['encerrado'] = true;
            }
        }

        foreach ($controle as $pessoaId => $info) {
            if ($info['faltas'] >= $minimo) {
                $resultado[] = [
                    'pessoa_id' => $pessoaId,
                    'nome' => $info['nome'],
                    'faltas_consecutivas' => $info['faltas']
                ];
            }
        }

        usort($resultado, function ($a, $b) {
            return $b['faltas_consecutivas'] <=> $a['faltas_consecutivas'];
        });

        return $resultado;
    }

    private function normalizarTextoOpcional(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor !== '' ? $valor : null;
    }

    private function grupoFamiliarExibicaoIdSql(string $alias): string
    {
        return "
            (
                SELECT gm.grupo_familiar_id
                FROM grupo_membros gm
                WHERE gm.pessoa_id = {$alias}.id
                  AND NOT EXISTS (
                      SELECT 1
                      FROM grupo_lideres gl
                      WHERE gl.pessoa_id = {$alias}.id
                        AND gl.grupo_familiar_id = gm.grupo_familiar_id
                  )
                ORDER BY gm.grupo_familiar_id
                LIMIT 1
            )
        ";
    }

    private function grupoFamiliarExibicaoNomeSql(string $grupoFamiliarExibicaoIdSql): string
    {
        return "
            (
                SELECT gf.nome
                FROM grupos_familiares gf
                WHERE gf.id = {$grupoFamiliarExibicaoIdSql}
                LIMIT 1
            )
        ";
    }

    private function normalizarInteiroOpcional($valor): ?int
    {
        if ($valor === null || $valor === '' || (int) $valor <= 0) {
            return null;
        }

        return (int) $valor;
    }

    private function atualizarGrupoPrincipalInterno(int $pessoaId, ?int $grupoId): void
    {
        $stmtAtual = $this->connection->prepare("
            SELECT grupo_familiar_id
            FROM pessoas
            WHERE id = :id
            LIMIT 1
        ");
        $stmtAtual->execute([':id' => $pessoaId]);
        $grupoAtual = $stmtAtual->fetchColumn();
        $grupoAtual = $grupoAtual !== false ? (int) $grupoAtual : null;
        $grupoAtual = $grupoAtual > 0 ? $grupoAtual : null;

        $this->validarGrupoMulheresPorGenero($pessoaId, $grupoId);

        $stmtPessoa = $this->connection->prepare("
            UPDATE pessoas
            SET grupo_familiar_id = :grupo_familiar_id
            WHERE id = :id
        ");
        $stmtPessoa->bindValue(':grupo_familiar_id', $grupoId, $grupoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmtPessoa->bindValue(':id', $pessoaId, PDO::PARAM_INT);
        $stmtPessoa->execute();

        if ($grupoAtual !== null && $grupoAtual !== $grupoId) {
            $stmtRemover = $this->connection->prepare("
                DELETE FROM grupo_membros
                WHERE pessoa_id = :pessoa_id
                  AND grupo_familiar_id = :grupo_id
                  AND NOT EXISTS (
                      SELECT 1
                      FROM grupo_lideres gl
                      WHERE gl.pessoa_id = :pessoa_id_lider
                        AND gl.grupo_familiar_id = :grupo_id_lider
                  )
            ");
            $stmtRemover->execute([
                ':pessoa_id' => $pessoaId,
                ':grupo_id' => $grupoAtual,
                ':pessoa_id_lider' => $pessoaId,
                ':grupo_id_lider' => $grupoAtual,
            ]);
        }

        if ($grupoId !== null) {
            $stmtInserir = $this->connection->prepare("
                INSERT INTO grupo_membros (grupo_familiar_id, pessoa_id)
                SELECT :grupo_id, :pessoa_id
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM grupo_membros
                    WHERE grupo_familiar_id = :grupo_id_existente
                      AND pessoa_id = :pessoa_id_existente
                )
            ");
            $stmtInserir->execute([
                ':grupo_id' => $grupoId,
                ':pessoa_id' => $pessoaId,
                ':grupo_id_existente' => $grupoId,
                ':pessoa_id_existente' => $pessoaId,
            ]);
        }
    }

    private function validarGrupoMulheresPorGenero(int $pessoaId, ?int $grupoId): void
    {
        if ($grupoId === null || $grupoId <= 0) {
            return;
        }

        $stmt = $this->connection->prepare("
            SELECT p.nome, COALESCE(p.genero, '') AS genero, gf.perfil_grupo
            FROM pessoas p
            INNER JOIN grupos_familiares gf ON gf.id = :grupo_id
            WHERE p.id = :pessoa_id
            LIMIT 1
        ");
        $stmt->execute([
            ':grupo_id' => $grupoId,
            ':pessoa_id' => $pessoaId,
        ]);

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$dados) {
            return;
        }

        if (($dados['perfil_grupo'] ?? '') === 'mulheres' && ($dados['genero'] ?? '') !== 'feminino') {
            throw new InvalidArgumentException('Somente pessoas com gênero Feminino podem participar de Grupo Familiar com perfil Mulheres.');
        }
    }
}
