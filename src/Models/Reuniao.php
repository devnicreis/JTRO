<?php

require_once __DIR__ . '/GrupoFamiliar.php';
require_once __DIR__ . '/Pessoa.php';
require_once __DIR__ . '/Presenca.php';

class Reuniao
{
    private GrupoFamiliar $grupoFamiliar;
    private string $data;
    private string $horario;
    private string $local;
    private ?string $motivoAlteracao = null;
    private ?string $observacoes = null;

    private array $presencas = [];

    public function __construct(
        GrupoFamiliar $grupoFamiliar,
        string $data,
        ?string $horario = null,
        ?string $local = null,
        ?string $motivoAlteracao = null
    ) {
        $this->grupoFamiliar = $grupoFamiliar;
        $this->data = $data;
        $this->horario = $horario ?? $grupoFamiliar->getHorario();
        $this->local = $local ?? "A definir";
        $this->motivoAlteracao = $motivoAlteracao;

        foreach ($grupoFamiliar->getMembros() as $membro) {
            $this->presencas[] = new Presenca($membro);
        }
    }

    public function getGrupoFamiliar(): GrupoFamiliar
    {
        return $this->grupoFamiliar;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getHorario(): string
    {
        return $this->horario;
    }

    public function getLocal(): string
    {
        return $this->local;
    }

    public function getMotivoAlteracao(): ?string
    {
        return $this->motivoAlteracao;
    }

    public function definirObservacoes(string $observacoes): void
    {
        $this->observacoes = $observacoes;
    }

    public function getObservacoes(): ?string
    {
        return $this->observacoes;
    }

    public function getPresencas(): array
    {
        return $this->presencas;
    }

    public function marcarAusente(Pessoa $pessoa): void
    {
        $encontrado = false;

        foreach ($this->presencas as $presenca) {
            if ($presenca->getPessoa()->getCpf() === $pessoa->getCpf()) {
                $presenca->marcarAusente();
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            throw new InvalidArgumentException("Essa pessoa não pertence à reunião.");
        }
    }

    public function marcarPresente(Pessoa $pessoa): void
    {
        $encontrado = false;

        foreach ($this->presencas as $presenca) {
            if ($presenca->getPessoa()->getCpf() === $pessoa->getCpf()) {
                $presenca->marcarPresente();
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            throw new InvalidArgumentException("Essa pessoa não pertence à reunião.");
        }
    }

    public function quantidadePresentes(): int
    {
        $total = 0;

        foreach ($this->presencas as $presenca) {
            if ($presenca->isPresente()) {
                $total++;
            }
        }

        return $total;
    }

    public function quantidadeAusentes(): int
    {
        $total = 0;

        foreach ($this->presencas as $presenca) {
            if ($presenca->isAusente()) {
                $total++;
            }
        }

        return $total;
    }

    public function getPresentes(): array
    {
        $presentes = [];

        foreach ($this->presencas as $presenca) {
            if ($presenca->isPresente()) {
                $presentes[] = $presenca->getPessoa();
            }
        }

        return $presentes;
    }

    public function getAusentes(): array
    {
        $ausentes = [];

        foreach ($this->presencas as $presenca) {
            if ($presenca->isAusente()) {
                $ausentes[] = $presenca->getPessoa();
            }
        }

        return $ausentes;
    }
}