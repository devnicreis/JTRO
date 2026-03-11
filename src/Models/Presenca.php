<?php

require_once __DIR__ . '/Pessoa.php';

class Presenca
{
    const STATUS_PRESENTE = 'presente';
    const STATUS_AUSENTE = 'ausente';

    private Pessoa $pessoa;
    private string $status;

    public function __construct(Pessoa $pessoa, string $status = self::STATUS_PRESENTE)
    {
        $this->validarStatus($status);

        $this->pessoa = $pessoa;
        $this->status = $status;
    }

    private function validarStatus(string $status): void
    {
        $statusValidos = [
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

    public function isPresente(): bool
    {
        return $this->status === self::STATUS_PRESENTE;
    }

    public function isAusente(): bool
    {
        return $this->status === self::STATUS_AUSENTE;
    }
}