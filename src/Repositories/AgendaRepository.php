<?php

require_once __DIR__ . '/../Core/Database.php';

class AgendaRepository
{
    private PDO $connection;
    private const MAX_IMPORT_BYTES = 1048576;
    private const MAX_IMPORT_EVENTS = 5000;

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

    public function importarIcs(string $conteudo, int $criadoPor, string $departamento = 'Pastoral'): array
    {
        $importados = 0;
        $ignorados  = 0;
        $totalOcorrencias = 0;
        $chavesImportadasNoLote = [];

        if (!in_array($departamento, self::DEPARTAMENTOS, true)) {
            throw new RuntimeException('Departamento invalido para importacao.');
        }

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
                $this->adicionarPropriedadeIcs($atual, $chave, $valor);
            }
        }

        $mapaExcecoesRecorrencia = $this->construirMapaExcecoesRecorrencia($eventos);

        foreach ($eventos as $ev) {
            $uid       = trim((string) $this->primeiroValorPropriedade($ev['UID'] ?? null));
            $dtstart   = $this->primeiroValorPropriedade($ev['DTSTART'] ?? null);
            $dtend     = $this->primeiroValorPropriedade($ev['DTEND'] ?? null);
            $summary   = $this->primeiroValorPropriedade($ev['SUMMARY'] ?? '(sem titulo)');
            $descricao = $this->primeiroValorPropriedade($ev['DESCRIPTION'] ?? null);
            $rrule     = $this->primeiroValorPropriedade($ev['RRULE'] ?? null);

            if (!$dtstart) {
                $ignorados++;
                continue;
            }

            [, $horarioFim] = $dtend ? $this->parsearDt((string) $dtend) : [null, null];
            $ocorrencias = $this->expandirOcorrenciasEvento(
                (string) $dtstart,
                is_string($rrule) ? $rrule : null,
                $ev['RDATE'] ?? null
            );

            if (empty($ocorrencias)) {
                $ignorados++;
                continue;
            }

            $exdates = $this->mapearExdates($ev['EXDATE'] ?? null);
            $isEventoMestreRecorrente = is_string($rrule) && $rrule !== '';

            foreach ($ocorrencias as $oc) {
                $totalOcorrencias++;
                if ($totalOcorrencias > self::MAX_IMPORT_EVENTS) {
                    throw new RuntimeException('O arquivo .ics ultrapassa o limite de eventos suportados.');
                }

                $chaveOcorrencia = $this->gerarChaveOcorrencia($oc['data'], $oc['horario']);
                if (isset($exdates[$chaveOcorrencia])) {
                    $ignorados++;
                    continue;
                }

                if (
                    $isEventoMestreRecorrente &&
                    $uid !== '' &&
                    isset($mapaExcecoesRecorrencia[$uid][$chaveOcorrencia])
                ) {
                    $ignorados++;
                    continue;
                }

                $gravou = $this->registrarEventoImportado(
                    (string) $summary,
                    $oc['data'],
                    $oc['horario'],
                    $horarioFim,
                    $departamento,
                    is_string($descricao) ? $descricao : null,
                    $criadoPor,
                    $chavesImportadasNoLote
                );

                if ($gravou) {
                    $importados++;
                } else {
                    $ignorados++;
                }
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

    private function adicionarPropriedadeIcs(array &$evento, string $chaveBruta, string $valorBruto): void
    {
        $chave = preg_replace('/;.*/', '', $chaveBruta);
        $valor = $this->decodificarIcs($valorBruto);

        if (!isset($evento[$chave])) {
            if (in_array($chave, ['EXDATE', 'RDATE'], true)) {
                $evento[$chave] = [$valor];
            } else {
                $evento[$chave] = $valor;
            }
            return;
        }

        if (!is_array($evento[$chave])) {
            $evento[$chave] = [$evento[$chave]];
        }
        $evento[$chave][] = $valor;
    }

    private function primeiroValorPropriedade(mixed $valor): mixed
    {
        if (is_array($valor)) {
            return $valor[0] ?? null;
        }
        return $valor;
    }

    private function normalizarListaPropriedade(mixed $valor): array
    {
        if ($valor === null) {
            return [];
        }

        if (!is_array($valor)) {
            return [$valor];
        }

        return $valor;
    }

    private function construirMapaExcecoesRecorrencia(array $eventos): array
    {
        $mapa = [];

        foreach ($eventos as $ev) {
            $uid = trim((string) $this->primeiroValorPropriedade($ev['UID'] ?? null));
            $recurrenceId = $this->primeiroValorPropriedade($ev['RECURRENCE-ID'] ?? null);
            if ($uid === '' || !is_string($recurrenceId) || $recurrenceId === '') {
                continue;
            }

            [$data, $horario] = $this->parsearDt($recurrenceId);
            if (!$data) {
                continue;
            }

            $mapa[$uid][$this->gerarChaveOcorrencia($data, $horario)] = true;
        }

        return $mapa;
    }

    private function mapearExdates(mixed $valorExdate): array
    {
        $exdates = [];

        foreach ($this->normalizarListaPropriedade($valorExdate) as $item) {
            if (!is_string($item)) {
                continue;
            }

            foreach (explode(',', $item) as $valorData) {
                $valorData = trim($valorData);
                if ($valorData === '') {
                    continue;
                }

                [$data, $horario] = $this->parsearDt($valorData);
                if ($data) {
                    $exdates[$this->gerarChaveOcorrencia($data, $horario)] = true;
                }
            }
        }

        return $exdates;
    }

    private function expandirOcorrenciasEvento(string $dtstart, ?string $rrule, mixed $rdate): array
    {
        [$dataBase, $horarioBase] = $this->parsearDt($dtstart);
        if (!$dataBase) {
            return [];
        }

        $ocorrencias = [[
            'data' => $dataBase,
            'horario' => $this->normalizarHorario($horarioBase),
        ]];

        if ($rrule !== null && trim($rrule) !== '') {
            foreach ($this->gerarDatasRecorrencia($dataBase, $rrule) as $dataRecorrente) {
                $ocorrencias[] = [
                    'data' => $dataRecorrente,
                    'horario' => $this->normalizarHorario($horarioBase),
                ];
            }
        }

        foreach ($this->normalizarListaPropriedade($rdate) as $item) {
            if (!is_string($item)) {
                continue;
            }

            foreach (explode(',', $item) as $valorData) {
                $valorData = trim($valorData);
                if ($valorData === '') {
                    continue;
                }

                [$dataRdate, $horarioRdate] = $this->parsearDt($valorData);
                if (!$dataRdate) {
                    continue;
                }

                $ocorrencias[] = [
                    'data' => $dataRdate,
                    'horario' => $this->normalizarHorario($horarioRdate ?: $horarioBase),
                ];
            }
        }

        $resultado = [];
        $chaves = [];
        foreach ($ocorrencias as $oc) {
            $chave = $this->gerarChaveOcorrencia($oc['data'], $oc['horario']);
            if (isset($chaves[$chave])) {
                continue;
            }
            $chaves[$chave] = true;
            $resultado[] = $oc;
        }

        usort($resultado, function (array $a, array $b): int {
            return strcmp($a['data'] . ' ' . $a['horario'], $b['data'] . ' ' . $b['horario']);
        });

        return $resultado;
    }

    private function parsearRrule(string $rrule): array
    {
        $partes = explode(';', strtoupper(trim($rrule)));
        $regra = [];

        foreach ($partes as $parte) {
            if (!str_contains($parte, '=')) {
                continue;
            }
            [$chave, $valor] = explode('=', $parte, 2);
            $regra[trim($chave)] = trim($valor);
        }

        return $regra;
    }

    private function gerarDatasRecorrencia(string $dataInicio, string $rrule): array
    {
        $inicio = DateTimeImmutable::createFromFormat('!Y-m-d', $dataInicio);
        if (!$inicio) {
            return [$dataInicio];
        }

        $regra = $this->parsearRrule($rrule);
        $freq = $regra['FREQ'] ?? '';
        if ($freq === '') {
            return [$dataInicio];
        }

        $interval = max(1, (int) ($regra['INTERVAL'] ?? 1));
        $count = isset($regra['COUNT']) ? max(1, (int) $regra['COUNT']) : null;
        $until = isset($regra['UNTIL']) ? $this->parsearDataIcs($regra['UNTIL']) : null;
        $fim = $until ?: $inicio->modify('+5 years');

        $byMonth = [];
        if (!empty($regra['BYMONTH'])) {
            foreach (explode(',', $regra['BYMONTH']) as $valor) {
                $mes = (int) trim($valor);
                if ($mes >= 1 && $mes <= 12) {
                    $byMonth[] = $mes;
                }
            }
        }

        $byMonthDay = [];
        if (!empty($regra['BYMONTHDAY'])) {
            foreach (explode(',', $regra['BYMONTHDAY']) as $valor) {
                $dia = (int) trim($valor);
                if ($dia !== 0 && $dia >= -31 && $dia <= 31) {
                    $byMonthDay[] = $dia;
                }
            }
        }

        $byDay = $this->parsearByDay($regra['BYDAY'] ?? null);

        $datas = [];
        $cursor = $inicio;

        while ($cursor <= $fim) {
            $match = $cursor->format('Y-m-d') === $dataInicio
                || $this->dataAtendeRecorrencia($cursor, $inicio, $freq, $interval, $byMonth, $byMonthDay, $byDay);

            if ($match) {
                $datas[] = $cursor->format('Y-m-d');

                if ($count !== null && count($datas) >= $count) {
                    break;
                }

                if (count($datas) >= self::MAX_IMPORT_EVENTS) {
                    break;
                }
            }

            $cursor = $cursor->modify('+1 day');
        }

        return $datas;
    }

    private function dataAtendeRecorrencia(
        DateTimeImmutable $data,
        DateTimeImmutable $inicio,
        string $freq,
        int $interval,
        array $byMonth,
        array $byMonthDay,
        array $byDay
    ): bool {
        $diasDiff = (int) $inicio->diff($data)->format('%r%a');
        if ($diasDiff < 0) {
            return false;
        }

        $base = false;
        if ($freq === 'DAILY') {
            $base = ($diasDiff % $interval) === 0;
        } elseif ($freq === 'WEEKLY') {
            $semanas = intdiv($diasDiff, 7);
            $base = ($semanas % $interval) === 0;
        } elseif ($freq === 'MONTHLY') {
            $meses = (((int) $data->format('Y')) * 12 + (int) $data->format('n'))
                - (((int) $inicio->format('Y')) * 12 + (int) $inicio->format('n'));
            $base = $meses >= 0 && ($meses % $interval) === 0;
        } elseif ($freq === 'YEARLY') {
            $anos = (int) $data->format('Y') - (int) $inicio->format('Y');
            $base = $anos >= 0 && ($anos % $interval) === 0;
        }

        if (!$base) {
            return false;
        }

        if (!empty($byMonth) && !in_array((int) $data->format('n'), $byMonth, true)) {
            return false;
        }

        if (!empty($byMonthDay) && !$this->bateByMonthDay($data, $byMonthDay)) {
            return false;
        }

        if (!empty($byDay) && !$this->bateByDay($data, $byDay, $freq)) {
            return false;
        }

        if ($freq === 'WEEKLY' && empty($byDay)) {
            return (int) $data->format('N') === (int) $inicio->format('N');
        }

        if ($freq === 'MONTHLY' && empty($byDay) && empty($byMonthDay)) {
            return (int) $data->format('j') === (int) $inicio->format('j');
        }

        if ($freq === 'YEARLY' && empty($byDay) && empty($byMonthDay)) {
            if (empty($byMonth)) {
                return $data->format('m-d') === $inicio->format('m-d');
            }

            return (int) $data->format('j') === (int) $inicio->format('j');
        }

        return true;
    }

    private function bateByMonthDay(DateTimeImmutable $data, array $byMonthDay): bool
    {
        $diaAtual = (int) $data->format('j');
        $ultimoDia = (int) $data->format('t');

        foreach ($byMonthDay as $diaRegra) {
            $diaAlvo = $diaRegra > 0 ? $diaRegra : ($ultimoDia + $diaRegra + 1);
            if ($diaAtual === $diaAlvo) {
                return true;
            }
        }

        return false;
    }

    private function bateByDay(DateTimeImmutable $data, array $byDay, string $freq): bool
    {
        $diaSemana = (int) $data->format('N');
        $diaDoMes = (int) $data->format('j');
        $ultimoDiaMes = (int) $data->format('t');

        foreach ($byDay as $regraDia) {
            if ($regraDia['weekday'] !== $diaSemana) {
                continue;
            }

            $ord = $regraDia['ord'];
            if ($ord === null) {
                return true;
            }

            if (!in_array($freq, ['MONTHLY', 'YEARLY'], true)) {
                return true;
            }

            if ($ord > 0) {
                $ocorrenciaNoMes = intdiv($diaDoMes - 1, 7) + 1;
                if ($ocorrenciaNoMes === $ord) {
                    return true;
                }
            } else {
                $ocorrenciaDoFim = intdiv($ultimoDiaMes - $diaDoMes, 7) + 1;
                if ($ocorrenciaDoFim === abs($ord)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function parsearByDay(?string $valor): array
    {
        if (!$valor) {
            return [];
        }

        $resultado = [];
        foreach (explode(',', strtoupper($valor)) as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }

            if (!preg_match('/^([+-]?\\d+)?(MO|TU|WE|TH|FR|SA|SU)$/', $item, $matches)) {
                continue;
            }

            $ord = ($matches[1] ?? '') !== '' ? (int) $matches[1] : null;
            $weekday = $this->diaSemanaNumero($matches[2]);
            if ($weekday === null) {
                continue;
            }

            $resultado[] = [
                'ord' => $ord,
                'weekday' => $weekday,
            ];
        }

        return $resultado;
    }

    private function diaSemanaNumero(string $sigla): ?int
    {
        return match ($sigla) {
            'MO' => 1,
            'TU' => 2,
            'WE' => 3,
            'TH' => 4,
            'FR' => 5,
            'SA' => 6,
            'SU' => 7,
            default => null,
        };
    }

    private function parsearDataIcs(string $valor): ?DateTimeImmutable
    {
        $valor = trim($valor);
        if (!preg_match('/(\\d{8})/', $valor, $matches)) {
            return null;
        }

        $data = DateTimeImmutable::createFromFormat('!Ymd', $matches[1]);
        return $data ?: null;
    }

    private function gerarChaveOcorrencia(string $data, ?string $horario): string
    {
        return $data . '|' . $this->normalizarHorario($horario);
    }

    private function normalizarHorario(?string $horario): string
    {
        if ($horario === null || trim($horario) === '') {
            return '00:00';
        }
        return substr(trim($horario), 0, 5);
    }

    private function registrarEventoImportado(
        string $titulo,
        string $data,
        string $horario,
        ?string $horarioFim,
        string $departamento,
        ?string $descricao,
        int $criadoPor,
        array &$chavesImportadasNoLote
    ): bool {
        $chaveDuplicidade = $this->gerarChaveDuplicidade($titulo, $data, $horario, $horarioFim);
        if (isset($chavesImportadasNoLote[$chaveDuplicidade])) {
            return false;
        }

        if ($this->eventoJaExisteNoBanco($titulo, $data, $horario, $horarioFim)) {
            return false;
        }

        try {
            $this->criar($titulo, $data, $horario, $horarioFim, $departamento, $descricao, $criadoPor);
            $chavesImportadasNoLote[$chaveDuplicidade] = true;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function gerarChaveDuplicidade(string $titulo, string $data, string $horario, ?string $horarioFim): string
    {
        $tituloNormalizado = strtolower(trim(preg_replace('/\\s+/', ' ', $titulo)));
        return implode('|', [
            $data,
            $this->normalizarHorario($horario),
            $this->normalizarHorario($horarioFim),
            $tituloNormalizado,
        ]);
    }

    private function eventoJaExisteNoBanco(string $titulo, string $data, string $horario, ?string $horarioFim): bool
    {
        $stmt = $this->connection->prepare("
            SELECT 1
            FROM eventos
            WHERE data = :data
              AND hora_inicio = :hora_inicio
              AND IFNULL(hora_fim, '') = IFNULL(:hora_fim, '')
              AND titulo = :titulo COLLATE NOCASE
            LIMIT 1
        ");
        $stmt->execute([
            ':data' => $data,
            ':hora_inicio' => $this->normalizarHorario($horario),
            ':hora_fim' => $horarioFim ? $this->normalizarHorario($horarioFim) : null,
            ':titulo' => trim($titulo),
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private function decodificarIcs(string $valor): string
    {
        return str_replace(['\\n','\\,','\\;','\\\\'], ["\n",',',';','\\'], $valor);
    }
}
