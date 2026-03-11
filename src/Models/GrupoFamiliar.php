<?php

require_once __DIR__ . '/Pessoa.php';

class GrupoFamiliar
{
    public string $nome;
    private array $lideres = [];
    private array $membros = [];
    private string $diaSemana;
    private string $horario;

    public function __construct(string $nome, array $lideres, string $diaSemana, string $horario)
    {
        if (count($lideres) === 0) {
            throw new InvalidArgumentException("O GF precisa ter pelo menos um líder.");
        }

        foreach ($lideres as $lider) {
            if (!$lider instanceof Pessoa) {
                throw new InvalidArgumentException("Todos os líderes precisam ser objetos Pessoa.");
            }

            if (!$lider->isLider() && !$lider->isAdmin()) {
                throw new InvalidArgumentException("O líder do GF precisa ter cargo de líder ou admin.");
            }
        }

        $this->nome = $nome;
        $this->lideres = $lideres;
        $this->diaSemana = $diaSemana;
        $this->horario = $horario;

        foreach ($lideres as $lider) {
            $this->membros[] = $lider;
        }
    }

    public function getLideres(): array
    {
        return $this->lideres;
    }

    public function getDiaSemana(): string
    {
        return $this->diaSemana;
    }

    public function getHorario(): string
    {
        return $this->horario;
    }

    public function adicionarMembro(Pessoa $pessoa): void
    {
        foreach ($this->membros as $membro) {
            if ($membro->getCpf() === $pessoa->getCpf()) {
                throw new InvalidArgumentException("Essa pessoa já pertence ao GF.");
            }
        }

        $this->membros[] = $pessoa;
    }

    public function getMembros(): array
    {
        return $this->membros;
    }

    public function quantidadeMembros(): int
    {
        return count($this->membros);
    }
}