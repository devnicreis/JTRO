<?php

function formatarDataBr(?string $data): string
{
    if (!$data) {
        return '—';
    }

    $date = DateTime::createFromFormat('Y-m-d', $data);

    if (!$date) {
        return $data;
    }

    return $date->format('d/m/Y');
}

function formatarDataHoraBr(?string $dataHora): string
{
    if (!$dataHora) {
        return '—';
    }

    $date = DateTime::createFromFormat('Y-m-d H:i:s', $dataHora);

    if (!$date) {
        return $dataHora;
    }

    return $date->format('d/m/Y H:i');
}