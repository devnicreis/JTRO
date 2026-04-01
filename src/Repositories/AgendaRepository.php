<?php

require_once __DIR__ . '/../Core/Database.php';

class AgendaRepository
{
    private PDO $connection;
    private const MAX_IMPORT_BYTES = 1048576;
    private const MAX_IMPORT_EVENTS = 500;

    public const DEPARTAMENTOS = [
        'Evento Geral',
        'Pastoral',
        'Evangelização',
        'Abba Jovem',
        'Abba Teen',
        'Mulheres',
        'EBI',
        'Artes',
        'Dança',
        'Música',
        'Backstage',
        'Comunicação',
        'Atividades Semanais',
    ];

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function criar(
        string $titulo,
        string $data,
        string $horario,
        ?string $horarioFim,
        string $departamento,
        ?string $descricao,
        int $criadoPor,
        ?string $uidIcs = null
    ): int {
        $stmt = $this->connection->prepare("
            INSERT INTO eventos
                (titulo, data, hora_inicio, hora_fim, departamento, descricao,
                 recorrente, criado_por, created_at)
            VALUES
                (:titulo, :data, :hora_inicio, :hora_fim, :departamento, :descricao,
                 0, :criado_por, :created_at)
        ");
        $stmt->execute([
            ':titulo'       => $titulo,
            ':data'         => $data,
            ':hora_inicio'  => $horario,
            ':hora_fim'     => $horarioFim ?: null,
            ':departamento' => $departamento,
            ':descricao'    => $descricao ?: null,
            ':criado_por'   => $criadoPor,
            ':created_at'   => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function atualizar(
        int $id,
        string $titulo,
        string $data,
        string $horario,
        ?string $horarioFim,
        string $departamento,
        ?string $descricao
    ): void {
        $stmt = $this->connection->prepare("
            UPDATE eventos
            SET titulo       = :titulo,
                data         = :data,
                hora_inicio  = :hora_inicio,
                hora_fim     = :hora_fim,
                departamento = :departamento,
                descricao    = :descricao,
                updated_at   = :updated_at
            WHERE id = :id
        ");
        $stmt->execute([
            ':titulo'       => $titulo,
            ':data'         => $data,
            ':hora_inicio'  => $horario,
            ':hora_fim'     => $horarioFim ?: null,
            ':departamento' => $departamento,
            ':descricao'    => $descricao ?: null,
            ':updated_at'   => date('Y-m-d H:i:s'),
            ':id'           => $id,
        ]);
    }

    public function excluir(int $id): void
    {
        $this->connection->prepare("DELETE FROM eventos WHERE id = :id")
            ->execute([':id' => $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM eventos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $row['horario']     = $row['hora_inicio'];
        $row['horario_fim'] = $row['hora_fim'] ?? null;
        return $row;
    }

    public function uidJaExiste(string $uid): bool
    {
        // tabela eventos não tem uid_ics — retorna false para não bloquear importação
        return false;
    }

    public function listarPorMes(int $ano, int $mes, ?string $departamento = null): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim    = sprintf('%04d-%02d-%02d', $ano, $mes, cal_days_in_month(CAL_GREGORIAN, $mes, $ano));

        $sql    = "SELECT * FROM eventos WHERE data BETWEEN :inicio AND :fim";
        $params = [':inicio' => $inicio, ':fim' => $fim];

        if ($departamento) {
            $sql .= " AND departamento = :dpto";
            $params[':dpto'] = $departamento;
        }

        $sql .= " ORDER BY data ASC, hora_inicio ASC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->adicionarAlias($rows);
    }

    public function listarPorDia(string $data, ?string $departamento = null): array
    {
        $sql    = "SELECT * FROM eventos WHERE data = :data";
        $params = [':data' => $data];

        if ($departamento) {
            $sql .= " AND departamento = :dpto";
            $params[':dpto'] = $departamento;
        }

        $sql .= " ORDER BY hora_inicio ASC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->adicionarAlias($rows);
    }

    public function listarDiasComEventos(int $ano, int $mes): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim    = sprintf('%04d-%02d-%02d', $ano, $mes, cal_days_in_month(CAL_GREGORIAN, $mes, $ano));

        $stmt = $this->connection->prepare(
            "SELECT DISTINCT data FROM eventos WHERE data BETWEEN :inicio AND :fim ORDER BY data ASC"
        );
        $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listarProximos(int $limite = 5): array
    {
        $stmt = $this->connection->prepare("
            SELECT * FROM eventos
            WHERE data >= :hoje
            ORDER BY data ASC, hora_inicio ASC
            LIMIT :limite
        ");
        $stmt->bindValue(':hoje', date('Y-m-d'));
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->adicionarAlias($rows);
    }

    public function importarIcs(string $conteudo, int $criadoPor): array
    {
        $importados = 0;
        $ignorados  = 0;

        if (strlen($conteudo) > self::MAX_IMPORT_BYTES) {
            throw new RuntimeException('O arquivo .ics excede o limite permitido.');
        }

        $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);
        $conteudo = preg_replace("/\n[ \t]/", '', $conteudo);

        if (!str_contains($conteudo, 'BEGIN:VCALENDAR') || !str_contains($conteudo, 'BEGIN:VEVENT')) {
            throw new RuntimeException('Arquivo .ics invalido.');
        }

        $eventos = [];
        $atual   = null;

        foreach (explode("\n", $conteudo) as $linha) {
            $linha = trim($linha);
            if ($linha === 'BEGIN:VEVENT') {
                $atual = [];
            } elseif ($linha === 'END:VEVENT' && $atual !== null) {
                $eventos[] = $atual;
                $atual = null;

                if (count($eventos) > self::MAX_IMPORT_EVENTS) {
                    throw new RuntimeException('O arquivo .ics ultrapassa o limite de eventos suportados.');
                }
            } elseif ($atual !== null && str_contains($linha, ':')) {
                [$chave, $valor] = explode(':', $linha, 2);
                $chave = preg_replace('/;.*/', '', $chave);
                $atual[$chave] = $this->decodificarIcs($valor);
            }
        }

        foreach ($eventos as $ev) {
            $dtstart   = $ev['DTSTART']     ?? null;
            $dtend     = $ev['DTEND']       ?? null;
            $summary   = $ev['SUMMARY']     ?? '(sem título)';
            $descricao = $ev['DESCRIPTION'] ?? null;

            if (!$dtstart) { $ignorados++; continue; }

            [$data, $horario]  = $this->parsearDt($dtstart);
            [, $horarioFim]    = $dtend ? $this->parsearDt($dtend) : [null, null];

            if (!$data) { $ignorados++; continue; }

            try {
                $this->criar($summary, $data, $horario ?? '00:00', $horarioFim, 'Pastoral', $descricao, $criadoPor);
                $importados++;
            } catch (Exception $e) {
                $ignorados++;
            }
        }

        return [$importados, $ignorados];
    }

    // Adiciona alias 'horario' => hora_inicio para compatibilidade com as views
    private function adicionarAlias(array $rows): array
    {
        return array_map(function($row) {
            $row['horario']    = $row['hora_inicio'];
            $row['horario_fim'] = $row['hora_fim'] ?? null;
            return $row;
        }, $rows);
    }

    private function parsearDt(string $dt): array
    {
        $dt = preg_replace('/Z$/', '', $dt);
        $dt = preg_replace('/\s/', '', $dt);
        $dt = str_replace('T', '', $dt);

        if (strlen($dt) >= 12) {
            $data    = substr($dt,0,4).'-'.substr($dt,4,2).'-'.substr($dt,6,2);
            $horario = substr($dt,8,2).':'.substr($dt,10,2);
        } elseif (strlen($dt) === 8) {
            $data    = substr($dt,0,4).'-'.substr($dt,4,2).'-'.substr($dt,6,2);
            $horario = '00:00';
        } else {
            return [null, null];
        }

        return [$data, $horario];
    }

    private function decodificarIcs(string $valor): string
    {
        return str_replace(['\\n','\\,','\\;','\\\\'], ["\n",',',';','\\'], $valor);
    }
}
