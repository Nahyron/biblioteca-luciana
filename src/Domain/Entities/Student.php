<?php

namespace App\Domain\Entities;

/**
 * Entidade Student (Representa um Aluno ou Usuário do Sistema).
 * Camada de Domínio: Regras de negócio essenciais.
 */
class Student
{
    public function __construct(
        public ?int $id = null,
        public string $nome = "",
        public string $turma = "",
        public ?string $faceDescriptor = null,
        public ?string $criadoAt = null,
        public ?string $rostoCadastradoAt = null,
        public ?string $ultimaEntradaAt = null
    ) {}

    /**
     * Converte os dados da entidade para um array associativo.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'turma' => $this->turma,
            'face_descriptor' => $this->faceDescriptor,
            'criado_at' => $this->criadoAt,
            'rosto_cadastrado_at' => $this->rostoCadastradoAt,
            'ultima_entrada_at' => $this->ultimaEntradaAt
        ];
    }
}
