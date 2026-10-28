<?php

class CalendarioGfService
{
    // Chave: dia_semana normalizado (minúsculo, sem acento). Valor: ISO-8601 (1 = segunda … 7 = domingo).
    private const DIAS_SEMANA = [
        'segunda-feira' => 1,
        'terca-feira'   => 2,
        'quarta-feira'  => 3,
        'quinta-feira'  => 4,
        'sexta-feira'   => 5,
        'sabado'        => 6,
        'domingo'       => 7,
    ];

    private const NOMES_DIAS = [
        1 => 'segunda-feira',
        2 => 'terça-feira',
        3 => 'quarta-feira',
        4 => 'quinta-feira',
        5 => 'sexta-feira',
        6 => 'sábado',
        7 => 'domingo',
    ];

    private const NOMES_MESES = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];

    private const ACENTOS = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e',
        'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c',
        'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'É' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ú' => 'u', 'Ç' => 'c',
    ];

    public function numeroDiaSemana(string $diaSemana): ?int
    {
        $normalizado = strtolower(strtr(trim($diaSemana), self::ACENTOS));
        return self::DIAS_SEMANA[$normalizado] ?? null;
    }

    /**
     * Ocorrência mais recente do dia_semana com data <= hoje. Se for hoje e o
     * horário ainda não chegou, usa a ocorrência de 7 dias antes.
     */
    public function dataPrevistaUltimaReuniao(string $diaSemana, string $horario, DateTimeImmutable $agora): ?DateTimeImmutable
    {
        $numero = $this->numeroDiaSemana($diaSemana);
        if ($numero === null) {
            return null;
        }

        $hoje = $this->inicioDoDia($agora);
        $diasAtras = ((int) $agora->format('N') - $numero + 7) % 7;
        $data = $hoje->modify("-{$diasAtras} days");

        if ($diasAtras === 0) {
            $horarioReuniao = $this->aplicarHorario($hoje, $horario);
            if ($horarioReuniao !== null && $agora < $horarioReuniao) {
                $data = $data->modify('-7 days');
            }
        }

        return $data;
    }

    /** Próxima data do dia_semana com data >= hoje (hoje conta mesmo se o horário já passou). */
    public function proximaOcorrencia(string $diaSemana, DateTimeImmutable $agora): ?DateTimeImmutable
    {
        $numero = $this->numeroDiaSemana($diaSemana);
        if ($numero === null) {
            return null;
        }

        $diasAFrente = ($numero - (int) $agora->format('N') + 7) % 7;
        return $this->inicioDoDia($agora)->modify("+{$diasAFrente} days");
    }

    /**
     * N-ésimo domingo do mês atual, se for >= hoje; senão, do próximo mês que
     * tiver o N-ésimo domingo. $n fora de 1..5 retorna null.
     */
    public function proximoDomingoDeOracao(int $n, DateTimeImmutable $agora): ?DateTimeImmutable
    {
        if ($n < 1 || $n > 5) {
            return null;
        }

        $hoje = $this->inicioDoDia($agora);
        $primeiroDoMes = $hoje->modify('first day of this month');

        // Todo mês tem ao menos 4 domingos e um 5º domingo aparece em até 3 meses.
        for ($i = 0; $i < 12; $i++) {
            $mes = $primeiroDoMes->modify("+{$i} months");
            $primeiroDomingo = 1 + (7 - (int) $mes->format('N')) % 7;
            $dia = $primeiroDomingo + 7 * ($n - 1);

            if ($dia > (int) $mes->format('t')) {
                continue;
            }

            $data = $mes->setDate((int) $mes->format('Y'), (int) $mes->format('n'), $dia);
            if ($data >= $hoje) {
                return $data;
            }
        }

        return null;
    }

    /** "Hoje", "Amanhã", "em N dias"; para datas passadas, "Ontem" e "há N dias". */
    public function rotuloRelativo(DateTimeImmutable $data, DateTimeImmutable $agora): string
    {
        $intervalo = $this->inicioDoDia($agora)->diff($this->inicioDoDia($data));
        $dias = (int) $intervalo->days * ($intervalo->invert === 1 ? -1 : 1);

        if ($dias === 0) {
            return 'Hoje';
        }
        if ($dias === 1) {
            return 'Amanhã';
        }
        if ($dias === -1) {
            return 'Ontem';
        }

        return $dias > 0 ? "em {$dias} dias" : 'há ' . abs($dias) . ' dias';
    }

    /** "Quarta-feira, 28 de outubro de 2026" (ou sem " de 2026" quando $comAno = false). */
    public function dataPorExtenso(DateTimeImmutable $data, bool $comAno = true): string
    {
        $texto = ucfirst(self::NOMES_DIAS[(int) $data->format('N')])
            . ', ' . $data->format('j')
            . ' de ' . self::NOMES_MESES[(int) $data->format('n')];

        return $comAno ? $texto . ' de ' . $data->format('Y') : $texto;
    }

    /** "sábado, 24/10" */
    public function diaCurto(DateTimeImmutable $data): string
    {
        return self::NOMES_DIAS[(int) $data->format('N')] . ', ' . $data->format('d/m');
    }

    public function saudacao(DateTimeImmutable $agora): string
    {
        $hora = (int) $agora->format('G');

        if ($hora >= 5 && $hora < 12) {
            return 'Bom dia';
        }
        if ($hora >= 12 && $hora < 18) {
            return 'Boa tarde';
        }

        return 'Boa noite';
    }

    private function inicioDoDia(DateTimeImmutable $data): DateTimeImmutable
    {
        return $data->setTime(0, 0, 0);
    }

    /** Aceita "H:i" ou "H:i:s". Retorna null se o horário for inválido. */
    private function aplicarHorario(DateTimeImmutable $dia, string $horario): ?DateTimeImmutable
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($horario), $partes)) {
            return null;
        }

        $hora = (int) $partes[1];
        $minuto = (int) $partes[2];
        $segundo = isset($partes[3]) ? (int) $partes[3] : 0;
        if ($hora > 23 || $minuto > 59 || $segundo > 59) {
            return null;
        }

        return $dia->setTime($hora, $minuto, $segundo);
    }
}
