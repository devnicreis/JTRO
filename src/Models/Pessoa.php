<?php

class Pessoa 
{
    public string $nome;
    public bool $ativo = true;

    const CARGO_MEMBRO = 'membro';
    const CARGO_LIDER = 'lider';
    const CARGO_ADMIN = 'admin';

    private string $cargo;
    private string $cpf;

    public function __construct(string $nome, string $cpf, string $cargo)
    {
        $this->validarCargo($cargo);

        $this->nome = $nome;
        $this->cpf = $cpf;
        $this->cargo = $cargo;
    }

    private function validarCargo(string $cargo): void
    {
        $cargosValidos = [
            self::CARGO_MEMBRO,
            self::CARGO_LIDER,
            self::CARGO_ADMIN
        ];

        if (!in_array($cargo, $cargosValidos, true)) {
            throw new InvalidArgumentException("Cargo inválido: {$cargo}");
        }
    }

    public function getCpf(): string
    {
        return $this->cpf;
    }

    public function desativar(): void
    {
        $this->ativo = false;
    }

    public function getCargo(): string
    {
        return $this->cargo; 
    }

    public function isLider(): bool
    {
        return $this->cargo === self::CARGO_LIDER;
    }

    public function isAdmin(): bool
    {
        return $this->cargo === self::CARGO_ADMIN;
    }
}