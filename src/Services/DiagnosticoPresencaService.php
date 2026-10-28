<?php

class DiagnosticoPresencaService
{
    private const LIMITE_OTIMO   = 90.0;
    private const LIMITE_BOM     = 70.0;
    private const LIMITE_ATENCAO = 51.0;

    private const CLASSIFICACOES = [
        'neutro'    => ['rotulo' => 'Sem registros', 'rotulo_maiusculo' => 'NEUTRO'],
        'otimo'     => ['rotulo' => 'Ótimo',         'rotulo_maiusculo' => 'ÓTIMO'],
        'bom'       => ['rotulo' => 'Bom',           'rotulo_maiusculo' => 'BOM'],
        'atencao'   => ['rotulo' => 'Atenção',       'rotulo_maiusculo' => 'ATENÇÃO'],
        'alarmante' => ['rotulo' => 'Alarmante',     'rotulo_maiusculo' => 'ALARMANTE'],
    ];

    /**
     * Régua única de diagnóstico de presença (mesma do Diagnóstico de GFs).
     * $totalRegistros = presenças + ausências.
     */
    public function classificar(float $percentual, int $totalRegistros): array
    {
        if ($totalRegistros <= 0) {
            $codigo = 'neutro';
        } elseif ($percentual >= self::LIMITE_OTIMO) {
            $codigo = 'otimo';
        } elseif ($percentual >= self::LIMITE_BOM) {
            $codigo = 'bom';
        } elseif ($percentual >= self::LIMITE_ATENCAO) {
            $codigo = 'atencao';
        } else {
            $codigo = 'alarmante';
        }

        return ['codigo' => $codigo] + self::CLASSIFICACOES[$codigo];
    }

    public function ehAlarmante(float $percentual, int $totalRegistros): bool
    {
        return $this->classificar($percentual, $totalRegistros)['codigo'] === 'alarmante';
    }
}
