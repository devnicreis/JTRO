<?php
require_once __DIR__ . '/../Core/Database.php';

class EventoRepository
{
    private PDO $connection;

    const DEPARTAMENTOS = [
        'Toda a Igreja',
        'Pastoral',
        'Evangelizacao',
        'Abba Jovem',
        'Abba Teen',
        'Mulheres',
        'EBI',
        'Teatro',
        'Danca',
        'Musica',
        'Backstage',
        'Comunicacao',
        'Espaco Externo',
    ];

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function criar(
        string $titulo,
        string $data,
        string $horaInicio,
        ?string $horaFim,
        string $departamento,
        ?string $descricao,
        int $recorrente,
        ?string $regraRecorrencia,
        int $criadoPor
    ): int {
        $stmt = $this->connection->prepare("
            INSERT INTO eventos
                (titulo, data, hora_inicio, hora_fim, departamento, descricao,
                 recorrente, regra_recorrencia, criado_por, created_at)
            VALUES
                (:titulo, :data, :hora_inicio, :hora_fim, :departamento, :descricao,
                 :recorrente, :regra_recorrencia, :criado_por, :created_at)
        ");
        $stmt->execute([
            ':titulo'            => $titulo,
            ':data'              => $data,
            ':hora_inicio'       => $horaInicio,
            ':hora_fim'          => $horaFim ?: null,
            ':departamento'      => $departamento,
            ':descricao'         => $descricao ?: null,
            ':recorrente'        => $recorrente,
            ':regra_recorrencia' => $regraRecorrencia ?: null,
            ':criado_por'        => $criadoPor,
            ':created_at'        => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function atualizar(
        int $id,
        string $titulo,
        string $data,
        string $horaInicio,
        ?string $horaFim,
        string $departamento,
        ?string $descricao
    ): void {
        $stmt = $this->connection->prepare("
            UPDATE eventos SET
                titulo       = :titulo,
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
            ':hora_inicio'  => $horaInicio,
            ':hora_fim'     => $horaFim ?: null,
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
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listarPorMes(int $ano, int $mes): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim    = date('Y-m-t', strtotime($inicio));
        $stmt = $this->connection->prepare("
            SELECT * FROM eventos
            WHERE data BETWEEN :inicio AND :fim
            ORDER BY data ASC, hora_inicio ASC
        ");
        $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorDia(string $data): array
    {
        $stmt = $this->connection->prepare("
            SELECT * FROM eventos WHERE data = :data ORDER BY hora_inicio ASC
        ");
        $stmt->execute([':data' => $data]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarProximos(int $limite = 10): array
    {
        $stmt = $this->connection->prepare("
            SELECT * FROM eventos
            WHERE data >= :hoje
            ORDER BY data ASC, hora_inicio ASC
            LIMIT :limite
        ");
        $stmt->execute([':hoje' => date('Y-m-d'), ':limite' => $limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorMesEDepartamento(int $ano, int $mes, ?string $departamento): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim    = date('Y-m-t', strtotime($inicio));

        if ($departamento) {
            $stmt = $this->connection->prepare("
                SELECT * FROM eventos
                WHERE data BETWEEN :inicio AND :fim AND departamento = :dept
                ORDER BY data ASC, hora_inicio ASC
            ");
            $stmt->execute([':inicio' => $inicio, ':fim' => $fim, ':dept' => $departamento]);
        } else {
            $stmt = $this->connection->prepare("
                SELECT * FROM eventos
                WHERE data BETWEEN :inicio AND :fim
                ORDER BY data ASC, hora_inicio ASC
            ");
            $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Importa eventos de um arquivo .ics (iCalendar)
     * Retorna [importados, ignorados]
     */
    public function importarIcs(string $conteudo, int $criadoPor): array
    {
        $importados = 0;
        $ignorados  = 0;

        // Normaliza quebras de linha
        $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);
        // Junta linhas dobradas (continuação com espaço/tab)
        $conteudo = preg_replace("/\n[ \t]/", '', $conteudo);

        // Extrai blocos VEVENT
        preg_match_all('/BEGIN:VEVENT(.+?)END:VEVENT/s', $conteudo, $matches);

        foreach ($matches[1] as $bloco) {
            $props = [];
            foreach (explode("\n", trim($bloco)) as $linha) {
                if (strpos($linha, ':') === false) continue;
                [$chave, $valor] = explode(':', $linha, 2);
                // Remove parâmetros (ex: DTSTART;TZID=America/Sao_Paulo)
                $chaveBase = explode(';', $chave)[0];
                $props[$chaveBase] = trim($valor);
            }

            // Extrai data e hora de DTSTART
            $dtstart = $props['DTSTART'] ?? '';
            if (empty($dtstart)) { $ignorados++; continue; }

            // Formato: 20260322T190000Z ou 20260322
            if (strlen($dtstart) >= 8) {
                $data       = substr($dtstart, 0, 4) . '-' . substr($dtstart, 4, 2) . '-' . substr($dtstart, 6, 2);
                $horaInicio = strlen($dtstart) > 8
                    ? substr($dtstart, 9, 2) . ':' . substr($dtstart, 11, 2)
                    : '00:00';
            } else {
                $ignorados++; continue;
            }

            // Hora fim
            $dtend   = $props['DTEND'] ?? '';
            $horaFim = null;
            if (strlen($dtend) > 8) {
                $horaFim = substr($dtend, 9, 2) . ':' . substr($dtend, 11, 2);
            }

            $titulo   = $this->decodificarIcs($props['SUMMARY'] ?? 'Evento sem título');
            $descricao = $this->decodificarIcs($props['DESCRIPTION'] ?? '');

            // Detecta departamento pelo título (heurística simples)
            $departamento = $this->detectarDepartamento($titulo);

            $this->criar(
                $titulo, $data, $horaInicio, $horaFim,
                $departamento, $descricao ?: null,
                0, null, $criadoPor
            );
            $importados++;
        }

        return [$importados, $ignorados];
    }

    private function decodificarIcs(string $texto): string
    {
        // Remove escapes do iCalendar
        $texto = str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $texto);
        return trim($texto);
    }

    private function detectarDepartamento(string $titulo): string
    {
        $titulo = mb_strtolower($titulo);
        if (str_contains($titulo, 'jovem') || str_contains($titulo, 'juvent')) return 'Abba Jovem';
        if (str_contains($titulo, 'teen') || str_contains($titulo, 'adolesc')) return 'Abba Teen';
        if (str_contains($titulo, 'mulher') || str_contains($titulo, 'feminino')) return 'Mulheres';
        if (str_contains($titulo, 'ebi') || str_contains($titulo, 'infantil') || str_contains($titulo, 'crianca')) return 'EBI';
        if (str_contains($titulo, 'teatro')) return 'Teatro';
        if (str_contains($titulo, 'danca') || str_contains($titulo, 'dança')) return 'Danca';
        if (str_contains($titulo, 'musica') || str_contains($titulo, 'música') || str_contains($titulo, 'louvor') || str_contains($titulo, 'ensaio')) return 'Musica';
        if (str_contains($titulo, 'comunica') || str_contains($titulo, 'midia') || str_contains($titulo, 'mídia')) return 'Comunicacao';
        if (str_contains($titulo, 'karate') || str_contains($titulo, 'pilates') || str_contains($titulo, 'escola de musica')) return 'Espaco Externo';
        if (str_contains($titulo, 'evangeliza') || str_contains($titulo, 'missao') || str_contains($titulo, 'missão')) return 'Evangelizacao';
        return 'Toda a Igreja';
    }
}