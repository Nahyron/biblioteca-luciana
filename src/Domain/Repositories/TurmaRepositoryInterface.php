<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Turma;

/**
 * Interface TurmaRepositoryInterface.
 * Define o contrato para manipulação de dados de turmas.
 */
interface TurmaRepositoryInterface
{
    public function getAll(): array;
    public function getInactive(): array;
    public function create(string $nome): bool|string;
    public function delete(int $id, ?string $operador = null): bool;
    public function deactivate(int $id, ?string $operador = null): bool;
    public function activate(int $id, ?string $operador = null): bool;
    public function updateName(int $id, string $newName): bool;
    public function getByName(string $nome): ?Turma;
}
