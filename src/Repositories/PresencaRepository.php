<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Presenca.php';

class PresencaRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    private function comprimentoTexto(string $texto): int
    {
        return function_exists('mb_strlen') ? mb_strlen($texto) : strlen($texto);
    }

    private function buscarGrupoPorId(int $grupoId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT id, nome, dia_semana, horario, local_padrao, local_fixo
            FROM grupos_familiares
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([':id' => $grupoId]);

        $grupo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $grupo ?: null;
    }

    private function obterDiaSemanaEmPortugues(string $data): string
    {
        $dias = [
            'Sunday' => 'domingo',
            'Monday' => 'segunda-feira',
            'Tuesday' => 'terça-feira',
            'Wednesday' => 'quarta-feira',
            'Thursday' => 'quinta-feira',
            'Friday' => 'sexta-feira',
            'Saturday' => 'sábado',
        ];

        $dateTime = new DateTime($data);
        $diaIngles = $dateTime->format('l');

        return $dias[$diaIngles] ?? '';
    }

    public function listarGruposFamiliares(): array
    {
        $sql = "
            SELECT id, nome, dia_semana, horario, local_padrao, local_fixo
            FROM grupos_familiares
            WHERE ativo = 1
            ORDER BY nome ASC
        ";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarReuniaoPorGrupoEData(int $grupoId, string $data): ?int
    {
        $dateTime = DateTime::createFromFormat('!Y-m-d', $data);

        if ($dateTime === false || $dateTime->format('Y-m-d') !== $data) {
            throw new InvalidArgumentException('Data da reunião inválida.');
        }

        $stmt = $this->connection->prepare("
        SELECT id
        FROM reunioes
        WHERE grupo_familiar_id = :grupo_id
        AND data = :data
        LIMIT 1
    ");

        $stmt->execute([
            ':grupo_id' => $grupoId,
            ':data' => $data
        ]);

        $reuniaoId = $stmt->fetchColumn();

        return $reuniaoId ? (int) $reuniaoId : null;
    }

    public function criarReuniaoPorGrupoEData(int $grupoId, string $data, ?string $horarioInformado = null): int
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $data);

        if ($dateTime === false || $dateTime->format('Y-m-d') !== $data) {
            throw new InvalidArgumentException('Data da reunião inválida.');
        }

        $reuniaoExistente = $this->buscarReuniaoPorGrupoEData($grupoId, $data);

        if ($reuniaoExistente !== null) {
            return $reuniaoExistente;
        }

        $grupo = $this->buscarGrupoPorId($grupoId);

        if (!$grupo) {
            throw new InvalidArgumentException('Grupo Familiar não encontrado.');
        }

        $horarioFinal = trim($horarioInformado ?? '') !== ''
            ? trim($horarioInformado)
            : $grupo['horario'];

        $motivos = [];

        $diaSemanaInformado = $this->obterDiaSemanaEmPortugues($data);

        if ($diaSemanaInformado !== '' && $diaSemanaInformado !== $grupo['dia_semana']) {
            $motivos[] = 'Reunião realizada fora do dia padrão do GF.';
        }

        if ($horarioFinal !== $grupo['horario']) {
            $motivos[] = 'Reunião realizada fora do horário padrão do GF.';
        }

        $motivoAlteracao = count($motivos) > 0 ? implode(' ', $motivos) : null;

        $this->connection->beginTransaction();

        try {
            $local = !empty($grupo['local_padrao'])
                ? $grupo['local_padrao']
                : '';


            $stmt = $this->connection->prepare("
    INSERT INTO reunioes (
        grupo_familiar_id,
        data,
        horario,
        local,
        motivo_alteracao,
        observacoes,
        finalizada
    ) VALUES (
        :grupo_familiar_id,
        :data,
        :horario,
        :local,
        :motivo_alteracao,
        NULL,
        0
    )
");

            $stmt->execute([
                ':grupo_familiar_id' => $grupoId,
                ':data' => $data,
                ':horario' => $horarioFinal,
                ':local' => $local,
                ':motivo_alteracao' => $motivoAlteracao
            ]);

            $novoReuniaoId = (int) $this->connection->lastInsertId();

            $stmtMembros = $this->connection->prepare("
                SELECT gm.pessoa_id
                FROM grupo_membros gm
                INNER JOIN pessoas p ON p.id = gm.pessoa_id
                WHERE gm.grupo_familiar_id = :grupo_id
                AND p.ativo = 1
            ");

            $stmtMembros->execute([':grupo_id' => $grupoId]);
            $membros = $stmtMembros->fetchAll(PDO::FETCH_COLUMN);

            $stmtPresenca = $this->connection->prepare("
                INSERT INTO presencas (reuniao_id, pessoa_id, status)
                VALUES (:reuniao_id, :pessoa_id, :status)
            ");

            foreach ($membros as $pessoaId) {
                $stmtPresenca->execute([
                    ':reuniao_id' => $novoReuniaoId,
                    ':pessoa_id' => (int) $pessoaId,
                    ':status' => Presenca::STATUS_PENDENTE
                ]);
            }

            $this->connection->commit();

            return $novoReuniaoId;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function buscarReuniao(int $reuniaoId): ?array
    {
        $stmt = $this->connection->prepare("
        SELECT
            r.id,
            r.grupo_familiar_id,
            r.data,
            r.horario,
            r.local,
            r.motivo_alteracao,
            r.observacoes,
            r.finalizada,
            gf.nome AS grupo_nome,
            gf.local_fixo,
            gf.local_padrao
        FROM reunioes r
        INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
        WHERE r.id = :id
        LIMIT 1
    ");

        $stmt->execute([':id' => $reuniaoId]);

        $reuniao = $stmt->fetch(PDO::FETCH_ASSOC);

        return $reuniao ?: null;
    }

    public function listarPresencasPorReuniao(int $reuniaoId): array
    {
        $sql = "
            SELECT
                p.id,
                p.reuniao_id,
                p.pessoa_id,
                p.status,
                pe.nome,
                pe.cpf,
                pe.cargo
            FROM presencas p
            INNER JOIN pessoas pe ON pe.id = p.pessoa_id
            WHERE p.reuniao_id = :reuniao_id
            ORDER BY pe.nome ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':reuniao_id' => $reuniaoId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atualizarReuniao(int $reuniaoId, string $local, string $observacoes): void
    {
        $limiteObservacoes = 500;

        if ($this->comprimentoTexto($observacoes) > $limiteObservacoes) {
            throw new InvalidArgumentException("O campo observações deve ter no máximo {$limiteObservacoes} caracteres.");
        }

        $stmtReuniao = $this->connection->prepare("
        SELECT
            r.id,
            r.local,
            r.motivo_alteracao,
            gf.local_padrao
        FROM reunioes r
        INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
        WHERE r.id = :id
        LIMIT 1
    ");

        $stmtReuniao->execute([':id' => $reuniaoId]);
        $reuniao = $stmtReuniao->fetch(PDO::FETCH_ASSOC);

        if (!$reuniao) {
            throw new InvalidArgumentException('Reunião não encontrada.');
        }

        if (trim($local) === '') {
            throw new InvalidArgumentException('Informe o local da reunião.');
        }

        $motivos = [];

        $motivoAtual = trim((string) ($reuniao['motivo_alteracao'] ?? ''));
        $localPadrao = trim((string) ($reuniao['local_padrao'] ?? ''));

        if ($motivoAtual !== '' && stripos($motivoAtual, 'local fora do padrão') === false) {
            $motivos[] = $motivoAtual;
        }

        if ($localPadrao !== '' && trim($local) !== $localPadrao) {
            $motivos[] = 'Reunião realizada em local fora do padrão do GF.';
        }

        $motivoAlteracao = count($motivos) > 0 ? implode(' ', $motivos) : null;

        $stmt = $this->connection->prepare("
        UPDATE reunioes
        SET local = :local,
            observacoes = :observacoes,
            motivo_alteracao = :motivo_alteracao
        WHERE id = :id
    ");

        $stmt->execute([
            ':local' => $local,
            ':observacoes' => $observacoes !== '' ? $observacoes : null,
            ':motivo_alteracao' => $motivoAlteracao,
            ':id' => $reuniaoId
        ]);
    }


    public function atualizarStatus(int $presencaId, string $status): void
    {
        $statusValidos = [
            Presenca::STATUS_PENDENTE,
            Presenca::STATUS_PRESENTE,
            Presenca::STATUS_AUSENTE
        ];

        if (!in_array($status, $statusValidos, true)) {
            throw new InvalidArgumentException('Status de presença inválido.');
        }

        $sql = "
            UPDATE presencas
            SET status = :status
            WHERE id = :id
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':status' => $status,
            ':id' => $presencaId
        ]);
    }

    public function atualizarPresencasEReuniao(int $reuniaoId, string $local, string $observacoes, array $presencas): void
    {
        $this->connection->beginTransaction();

        try {
            $this->atualizarReuniao($reuniaoId, $local, $observacoes);

            foreach ($presencas as $presencaId => $status) {
                $this->atualizarStatus((int) $presencaId, $status);
            }

            $stmtFinalizar = $this->connection->prepare("
            UPDATE reunioes
            SET finalizada = 1
            WHERE id = :id
        ");

            $stmtFinalizar->execute([
                ':id' => $reuniaoId
            ]);

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function listarGruposFamiliaresPorLider(int $pessoaId): array
    {
        $sql = "
            SELECT gf.id, gf.nome, gf.dia_semana, gf.horario, gf.local_padrao, gf.local_fixo
            FROM grupos_familiares gf
            INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
            WHERE gf.ativo = 1
            AND gl.pessoa_id = :pessoa_id
            ORDER BY gf.nome ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':pessoa_id' => $pessoaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function liderPodeAcessarGrupo(int $pessoaId, int $grupoId): bool
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*)
            FROM grupo_lideres gl
            INNER JOIN grupos_familiares gf ON gf.id = gl.grupo_familiar_id
            WHERE gl.pessoa_id = :pessoa_id
            AND gl.grupo_familiar_id = :grupo_id
            AND gf.ativo = 1
        ");

        $stmt->execute([
            ':pessoa_id' => $pessoaId,
            ':grupo_id' => $grupoId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function buscarResumoGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                gf.id,
                gf.nome,
                gf.dia_semana,
                gf.horario,
                gf.local_padrao,
                gf.local_fixo,
                (
                    SELECT COUNT(*)
                    FROM grupo_membros gm
                    INNER JOIN pessoas p ON p.id = gm.pessoa_id
                    WHERE gm.grupo_familiar_id = gf.id
                    AND p.ativo = 1
                ) AS total_membros_ativos,
                (
                    SELECT COUNT(*)
                    FROM reunioes r
                    WHERE r.grupo_familiar_id = gf.id
                    AND r.finalizada = 1
                ) AS total_reunioes,
                (
                    SELECT MAX(r.data)
                    FROM reunioes r
                    WHERE r.grupo_familiar_id = gf.id
                    AND r.finalizada = 1
                ) AS ultima_data_reuniao
            FROM grupos_familiares gf
            WHERE gf.id = :grupo_id
            LIMIT 1
        ");

        $stmt->execute([':grupo_id' => $grupoId]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: [];
    }

    public function listarUltimasReunioesDoGrupo(int $grupoId, int $limite = 5): array
    {
        $limite = max(1, $limite);

        $sql = "
            SELECT
                r.id,
                r.data,
                r.horario,
                r.local,
                r.observacoes,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                    AND p.status = 'presente'
                ) AS total_presentes,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                    AND p.status = 'ausente'
                ) AS total_ausentes
            FROM reunioes r
            WHERE r.grupo_familiar_id = :grupo_id
            AND r.finalizada = 1
            ORDER BY r.data DESC, r.id DESC
            LIMIT {$limite}
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':grupo_id' => $grupoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarReunioes(): int
    {
        $stmt = $this->connection->query("
            SELECT COUNT(*)
            FROM reunioes
        ");

        return (int) $stmt->fetchColumn();
    }

    public function listarUltimasReunioesGerais(int $limite = 5): array
    {
        $limite = max(1, (int) $limite);

        $sql = "
            SELECT
                r.id,
                r.data,
                r.horario,
                r.local,
                r.motivo_alteracao,
                gf.nome AS grupo_nome,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                    AND p.status = 'presente'
                ) AS total_presentes,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                    AND p.status = 'ausente'
                ) AS total_ausentes
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            AND r.finalizada = 1
            ORDER BY r.data DESC, r.id DESC
            LIMIT {$limite}
        ";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarUltimasReunioesDoLider(int $pessoaId, int $limite = 5): array
    {
        $limite = max(1, (int) $limite);

        $sql = "
            SELECT
                r.id,
                r.data,
                r.horario,
                r.local,
                r.motivo_alteracao,
                gf.nome AS grupo_nome,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                    AND p.status = 'presente'
                ) AS total_presentes,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                    AND p.status = 'ausente'
                ) AS total_ausentes
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
            WHERE gl.pessoa_id = :pessoa_id
            AND r.finalizada = 1
            ORDER BY r.data DESC, r.id DESC
            LIMIT {$limite}
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':pessoa_id' => $pessoaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        AND r.finalizada = 1
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
        unset($linha);

        return $linhas;
    }

    public function buscarMembrosComFaltasConsecutivasGerais(int $minimo = 2): array
    {
        $stmt = $this->connection->query("
            SELECT
                gf.id AS grupo_id,
                gf.nome AS grupo_nome,
                (
                    SELECT GROUP_CONCAT(p2.nome, ', ')
                    FROM grupo_lideres gl2
                    INNER JOIN pessoas p2 ON p2.id = gl2.pessoa_id
                    WHERE gl2.grupo_familiar_id = gf.id
                    AND p2.ativo = 1
                ) AS lideres,
                pe.id AS pessoa_id,
                pe.nome,
                r.data,
                p.status
            FROM grupo_membros gm
            INNER JOIN pessoas pe ON pe.id = gm.pessoa_id
            INNER JOIN grupos_familiares gf ON gf.id = gm.grupo_familiar_id
            INNER JOIN reunioes r ON r.grupo_familiar_id = gm.grupo_familiar_id
            INNER JOIN presencas p ON p.reuniao_id = r.id AND p.pessoa_id = pe.id
            WHERE pe.ativo = 1
            AND gf.ativo = 1
            ORDER BY gf.id ASC, pe.id ASC, r.data DESC, r.id DESC
        ");

        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        $controle = [];

        foreach ($linhas as $linha) {
            $chave = $linha['grupo_id'] . '-' . $linha['pessoa_id'];

            if (!isset($controle[$chave])) {
                $controle[$chave] = [
                    'grupo_id' => (int) $linha['grupo_id'],
                    'grupo_nome' => $linha['grupo_nome'],
                    'lideres' => $linha['lideres'],
                    'pessoa_id' => (int) $linha['pessoa_id'],
                    'nome' => $linha['nome'],
                    'faltas' => 0,
                    'encerrado' => false
                ];
            }

            if ($controle[$chave]['encerrado']) {
                continue;
            }

            if ($linha['status'] === 'ausente') {
                $controle[$chave]['faltas']++;
            } else {
                $controle[$chave]['encerrado'] = true;
            }
        }

        foreach ($controle as $item) {
            if ($item['faltas'] >= $minimo) {
                $resultado[] = [
                    'grupo_id' => $item['grupo_id'],
                    'grupo_nome' => $item['grupo_nome'],
                    'lideres' => $item['lideres'],
                    'pessoa_id' => $item['pessoa_id'],
                    'nome' => $item['nome'],
                    'faltas_consecutivas' => $item['faltas']
                ];
            }
        }

        usort($resultado, function ($a, $b) {
            return $b['faltas_consecutivas'] <=> $a['faltas_consecutivas'];
        });

        return $resultado;
    }

    public function buscarMembrosComFaltasConsecutivasDoLider(int $pessoaId, int $minimo = 2): array
    {
        $grupos = $this->listarGruposFamiliaresPorLider($pessoaId);
        $resultado = [];

        foreach ($grupos as $grupo) {
            $faltosos = $this->buscarMembrosComFaltasConsecutivas((int) $grupo['id'], $minimo);

            foreach ($faltosos as $faltoso) {
                $resultado[] = [
                    'grupo_id' => (int) $grupo['id'],
                    'grupo_nome' => $grupo['nome'],
                    'lideres' => null,
                    'pessoa_id' => $faltoso['pessoa_id'],
                    'nome' => $faltoso['nome'],
                    'faltas_consecutivas' => $faltoso['faltas_consecutivas']
                ];
            }
        }

        usort($resultado, function ($a, $b) {
            return $b['faltas_consecutivas'] <=> $a['faltas_consecutivas'];
        });

        return $resultado;
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

    public function buscarGruposAlarmantes(): array
    {
        $stmt = $this->connection->query("
        SELECT
            gf.id,
            gf.nome,
            gf.dia_semana,
            gf.horario,
            (
                SELECT GROUP_CONCAT(p.nome, ', ')
                FROM grupo_lideres gl
                INNER JOIN pessoas p ON p.id = gl.pessoa_id
                WHERE gl.grupo_familiar_id = gf.id
                AND p.ativo = 1
            ) AS lideres
        FROM grupos_familiares gf
        WHERE gf.ativo = 1
        ORDER BY gf.nome ASC
    ");

        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado = [];

        foreach ($grupos as $grupo) {
            $resumo = $this->buscarResumoPresencaPorGrupo((int) $grupo['id']);
            $totalRegistros = (int) $resumo['total_presencas'] + (int) $resumo['total_ausencias'];

            if ($totalRegistros === 0) {
                continue;
            }

            if ((float) $resumo['percentual_presencas'] < 50) {
                $grupo['resumo_presenca'] = $resumo;
                $resultado[] = $grupo;
            }
        }

        return $resultado;
    }

    public function buscarGruposAlarmantesDoLider(int $pessoaId): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            gf.id,
            gf.nome,
            gf.dia_semana,
            gf.horario,
            (
                SELECT GROUP_CONCAT(p2.nome, ', ')
                FROM grupo_lideres gl2
                INNER JOIN pessoas p2 ON p2.id = gl2.pessoa_id
                WHERE gl2.grupo_familiar_id = gf.id
                AND p2.ativo = 1
            ) AS lideres
        FROM grupos_familiares gf
        INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
        WHERE gf.ativo = 1
        AND gl.pessoa_id = :pessoa_id
        ORDER BY gf.nome ASC
    ");

        $stmt->execute([':pessoa_id' => $pessoaId]);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];

        foreach ($grupos as $grupo) {
            $resumo = $this->buscarResumoPresencaPorGrupo((int) $grupo['id']);
            $totalRegistros = (int) $resumo['total_presencas'] + (int) $resumo['total_ausencias'];

            if ($totalRegistros === 0) {
                continue;
            }

            if ((float) $resumo['percentual_presencas'] < 50) {
                $grupo['resumo_presenca'] = $resumo;
                $resultado[] = $grupo;
            }
        }

        return $resultado;
    }

    public function buscarReunioesForaDoPadrao(int $limite = 20): array
    {
        $limite = max(1, (int) $limite);

        $sql = "
        SELECT
            r.id,
            r.data,
            r.horario,
            r.motivo_alteracao,
            gf.id AS grupo_id,
            gf.nome AS grupo_nome,
            (
                SELECT GROUP_CONCAT(p.nome, ', ')
                FROM grupo_lideres gl
                INNER JOIN pessoas p ON p.id = gl.pessoa_id
                WHERE gl.grupo_familiar_id = gf.id
                AND p.ativo = 1
            ) AS lideres
        FROM reunioes r
        INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
        WHERE gf.ativo = 1
        AND r.finalizada = 1
        AND r.motivo_alteracao IS NOT NULL
        AND TRIM(r.motivo_alteracao) <> ''
        ORDER BY r.data DESC, r.id DESC
        LIMIT {$limite}
    ";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarReunioesForaDoPadraoDoLider(int $pessoaId, int $limite = 20): array
    {
        $limite = max(1, (int) $limite);

        $sql = "
        SELECT
            r.id,
            r.data,
            r.horario,
            r.motivo_alteracao,
            gf.id AS grupo_id,
            gf.nome AS grupo_nome,
            (
                SELECT GROUP_CONCAT(p2.nome, ', ')
                FROM grupo_lideres gl2
                INNER JOIN pessoas p2 ON p2.id = gl2.pessoa_id
                WHERE gl2.grupo_familiar_id = gf.id
                AND p2.ativo = 1
            ) AS lideres
        FROM reunioes r
        INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
        INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
        WHERE gf.ativo = 1
        AND r.finalizada = 1
        AND gl.pessoa_id = :pessoa_id
        AND r.motivo_alteracao IS NOT NULL
        AND TRIM(r.motivo_alteracao) <> ''
        ORDER BY r.data DESC, r.id DESC
        LIMIT {$limite}
    ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':pessoa_id' => $pessoaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarResumoDaReuniao(int $reuniaoId): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            COUNT(*) AS total_registros,
            SUM(CASE WHEN status = 'presente' THEN 1 ELSE 0 END) AS total_presentes,
            SUM(CASE WHEN status = 'ausente' THEN 1 ELSE 0 END) AS total_ausentes
        FROM presencas
        WHERE reuniao_id = :reuniao_id
    ");

        $stmt->execute([':reuniao_id' => $reuniaoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalRegistros = (int) ($resultado['total_registros'] ?? 0);
        $totalPresentes = (int) ($resultado['total_presentes'] ?? 0);
        $totalAusentes = (int) ($resultado['total_ausentes'] ?? 0);

        return [
            'total_registros' => $totalRegistros,
            'total_presentes' => $totalPresentes,
            'total_ausentes' => $totalAusentes,
            'percentual_presencas' => $totalRegistros > 0 ? round(($totalPresentes / $totalRegistros) * 100, 1) : 0,
            'percentual_ausencias' => $totalRegistros > 0 ? round(($totalAusentes / $totalRegistros) * 100, 1) : 0,
        ];
    }

    public function buscarLideresDoGrupo(int $grupoId): string
    {
        $stmt = $this->connection->prepare("
        SELECT GROUP_CONCAT(p.nome, ', ')
        FROM grupo_lideres gl
        INNER JOIN pessoas p ON p.id = gl.pessoa_id
        WHERE gl.grupo_familiar_id = :grupo_id
        AND p.ativo = 1
    ");

        $stmt->execute([':grupo_id' => $grupoId]);

        $lideres = $stmt->fetchColumn();

        return $lideres ?: '—';
    }

    public function listarPedidosOracaoPorReuniao(int $reuniaoId): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            po.id,
            po.reuniao_id,
            po.pessoa_id,
            po.pedido,
            p.nome
        FROM pedidos_oracao po
        INNER JOIN pessoas p ON p.id = po.pessoa_id
        WHERE po.reuniao_id = :reuniao_id
        ORDER BY p.nome ASC
    ");

        $stmt->execute([':reuniao_id' => $reuniaoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvarPedidosOracao(int $reuniaoId, array $pedidos): void
    {
        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare("
            INSERT INTO pedidos_oracao (
                reuniao_id,
                pessoa_id,
                pedido,
                created_at,
                updated_at
            ) VALUES (
                :reuniao_id,
                :pessoa_id,
                :pedido,
                :created_at,
                :updated_at
            )
            ON CONFLICT(reuniao_id, pessoa_id)
            DO UPDATE SET
                pedido = excluded.pedido,
                updated_at = excluded.updated_at
        ");

            foreach ($pedidos as $pessoaId => $pedido) {
                $pedido = trim((string) $pedido);

                $stmt->execute([
                    ':reuniao_id' => $reuniaoId,
                    ':pessoa_id' => (int) $pessoaId,
                    ':pedido' => $pedido !== '' ? $pedido : null,
                    ':created_at' => date('Y-m-d H:i:s'),
                    ':updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function listarPresentesDaReuniao(int $reuniaoId): array
    {
        $stmt = $this->connection->prepare("
        SELECT
            p.pessoa_id,
            pe.nome
        FROM presencas p
        INNER JOIN pessoas pe ON pe.id = p.pessoa_id
        WHERE p.reuniao_id = :reuniao_id
        AND p.status = 'presente'
        ORDER BY pe.nome ASC
    ");

        $stmt->execute([':reuniao_id' => $reuniaoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reuniaoTemPresencasPendentes(int $reuniaoId): bool
    {
        $stmt = $this->connection->prepare("
        SELECT COUNT(*)
        FROM presencas
        WHERE reuniao_id = :reuniao_id
        AND status = :status
    ");

        $stmt->execute([
            ':reuniao_id' => $reuniaoId,
            ':status' => Presenca::STATUS_PENDENTE
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function contarPresencasDaReuniao(int $reuniaoId): int
    {
        $stmt = $this->connection->prepare("
        SELECT COUNT(*)
        FROM presencas
        WHERE reuniao_id = :reuniao_id
    ");

        $stmt->execute([':reuniao_id' => $reuniaoId]);

        return (int) $stmt->fetchColumn();
    }

    // Adicionar este método ao PresencaRepository.php
// Cria a reunião e salva presenças em uma única transação atômica

public function criarReuniaoComPresencas(
    int $grupoId,
    string $data,
    string $horario,
    string $local,
    ?string $observacoes,
    array $presencas // [presenca_id_temporario => status] — na verdade [pessoa_id => status]
): int {
    $dateTime = DateTime::createFromFormat('Y-m-d', $data);
    if ($dateTime === false || $dateTime->format('Y-m-d') !== $data) {
        throw new InvalidArgumentException('Data da reunião inválida.');
    }

    $existente = $this->buscarReuniaoPorGrupoEData($grupoId, $data);
    if ($existente !== null) {
        throw new InvalidArgumentException('Já existe uma reunião registrada para este GF nessa data.');
    }

    $grupo = $this->buscarGrupoPorId($grupoId);
    if (!$grupo) {
        throw new InvalidArgumentException('Grupo Familiar não encontrado.');
    }

    $motivos = [];
    $diaSemanaInformado = $this->obterDiaSemanaEmPortugues($data);
    if ($diaSemanaInformado !== '' && $diaSemanaInformado !== $grupo['dia_semana']) {
        $motivos[] = 'Reunião realizada fora do dia padrão do GF.';
    }
    if ($horario !== $grupo['horario']) {
        $motivos[] = 'Reunião realizada fora do horário padrão do GF.';
    }
    if (!empty($grupo['local_padrao']) && trim($local) !== trim($grupo['local_padrao'])) {
        $motivos[] = 'Reunião realizada em local fora do padrão do GF.';
    }
    $motivoAlteracao = count($motivos) > 0 ? implode(' ', $motivos) : null;

    $this->connection->beginTransaction();
    try {
        $stmt = $this->connection->prepare("
            INSERT INTO reunioes (grupo_familiar_id, data, horario, local, motivo_alteracao, observacoes)
            VALUES (:grupo_familiar_id, :data, :horario, :local, :motivo_alteracao, :observacoes)
        ");
        $stmt->execute([
            ':grupo_familiar_id' => $grupoId,
            ':data'              => $data,
            ':horario'           => $horario,
            ':local'             => $local,
            ':motivo_alteracao'  => $motivoAlteracao,
            ':observacoes'       => $observacoes !== '' ? $observacoes : null,
        ]);
        $reuniaoId = (int) $this->connection->lastInsertId();

        $stmtP = $this->connection->prepare("
            INSERT INTO presencas (reuniao_id, pessoa_id, status)
            VALUES (:reuniao_id, :pessoa_id, :status)
        ");
        foreach ($presencas as $pessoaId => $status) {
            if (!in_array($status, ['presente', 'ausente'], true)) {
                throw new InvalidArgumentException('Status inválido para o membro ' . $pessoaId);
            }
            $stmtP->execute([
                ':reuniao_id' => $reuniaoId,
                ':pessoa_id'  => (int) $pessoaId,
                ':status'     => $status,
            ]);
        }

        $this->connection->commit();
        return $reuniaoId;
    } catch (Exception $e) {
        $this->connection->rollBack();
        throw $e;
    }
}

// Buscar membros ativos de um grupo (para pré-carregar o formulário antes de criar a reunião)
public function listarMembrosPorGrupo(int $grupoId): array
{
    $stmt = $this->connection->prepare("
        SELECT gm.pessoa_id AS id, p.nome, p.cargo
        FROM grupo_membros gm
        INNER JOIN pessoas p ON p.id = gm.pessoa_id
        WHERE gm.grupo_familiar_id = :grupo_id
        AND p.ativo = 1
        ORDER BY p.nome ASC
    ");
    $stmt->execute([':grupo_id' => $grupoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
