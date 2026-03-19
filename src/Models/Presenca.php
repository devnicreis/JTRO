<?php

require_once __DIR__ . '/Pessoa.php';

class Presenca
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_PRESENTE = 'presente';
    public const STATUS_AUSENTE = 'ausente';

    private Pessoa $pessoa;
    private string $status;

    public function __construct(Pessoa $pessoa, string $status = self::STATUS_PENDENTE)
    {
        $this->validarStatus($status);

        $this->pessoa = $pessoa;
        $this->status = $status;
    }

    private function validarStatus(string $status): void
    {
        $statusValidos = [
            self::STATUS_PENDENTE,
            self::STATUS_PRESENTE,
            self::STATUS_AUSENTE
        ];

        if (!in_array($status, $statusValidos, true)) {
            throw new InvalidArgumentException("Status de presença inválido: {$status}");
        }
    }

    public function getPessoa(): Pessoa
    {
        return $this->pessoa;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function marcarAusente(): void
    {
        $this->status = self::STATUS_AUSENTE;
    }

    public function marcarPresente(): void
    {
        $this->status = self::STATUS_PRESENTE;
    }

    public function marcarPendente(): void
    {
        $this->status = self::STATUS_PENDENTE;
    }

    public function isPresente(): bool
    {
        return $this->status === self::STATUS_PRESENTE;
    }

    public function isAusente(): bool
    {
        return $this->status === self::STATUS_AUSENTE;
    }

    public function isPendente(): bool
    {
        return $this->status === self::STATUS_PENDENTE;
    }
}
