<?php

namespace App\Application\Services;

use App\Domain\Repositories\TurmaRepositoryInterface;

/**
 * Serviço de Aplicação para Turmas.
 * Camada de Aplicação: Orquestra Use Cases.
 */
class TurmaService
{
    public function __construct(private TurmaRepositoryInterface $repository) {}

    public function listAll(): array
    {
        $turmas = $this->repository->getAll();
        return array_map(fn($t) => $t->toArray(), $turmas);
    }

    public function listInactive(): array
    {
        $turmas = $this->repository->getInactive();
        return array_map(fn($t) => $t->toArray(), $turmas);
    }

    public function create(string $nome): bool|string
    {
        $nome = trim($nome);
        if (empty($nome)) {
            return "O nome da turma não pode estar vazio.";
        }

        // Validação estrita de segurança para o nome da turma
        if (!preg_match('/^[\p{L}\p{N}\s\-ºª]+$/u', $nome)) {
            return "Caracteres especiais não permitidos no nome da turma.";
        }

        $nomeClean = preg_replace('/[^\p{L}\p{N}\s\-ºª]/u', '', $nome);
        return $this->repository->create($nomeClean);
    }

    /**
     * Desativa a turma (soft delete) em vez de excluir permanentemente.
     */
    public function deactivate(int $id, ?string $operador = null): bool
    {
        return $this->repository->deactivate($id, $operador);
    }

    /**
     * Ativa uma turma desativada. Permite ativar mesmo com mesmo nome de outra ativa.
     */
    public function activate(int $id, ?string $operador = null): bool
    {
        return $this->repository->activate($id, $operador);
    }

    /**
     * Alias para compatibilidade — delega para deactivate.
     */
    public function delete(int $id, ?string $operador = null): bool
    {
        return $this->deactivate($id, $operador);
    }

    public function updateName(int $id, string $newName): bool
    {
        $newName = trim($newName);
        if (empty($newName) || !preg_match('/^[\p{L}\p{N}\s\-ºª]+$/u', $newName)) {
            return false;
        }

        $newNameClean = preg_replace('/[^\p{L}\p{N}\s\-ºª]/u', '', $newName);
        return $this->repository->updateName($id, $newNameClean);
    }
}
