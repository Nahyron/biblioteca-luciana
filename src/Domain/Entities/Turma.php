<?php

namespace App\Domain\Entities;

/**
 * Entidade Turma (Representa uma classe no sistema).
 * Camada de Domínio: Independente de Banco de Dados.
 */
class Turma
{
    public function __construct(
        public ?int $id = null,
        public string $nome = "",
        public ?string $createdAt = null,
        public int $ativo = 1,
        public ?int $criadorId = null,
        public ?string $criadorTipo = null
    ) {}

    /**
     * Converte os dados da entidade para um array associativo.
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'nome'         => $this->nome,
            'created_at'   => $this->createdAt,
            'ativo'        => $this->ativo,
            'criador_id'   => $this->criadorId,
            'criador_tipo' => $this->criadorTipo
        ];
    }
}
