<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Student;

/**
 * Interface StudentRepositoryInterface.
 * Define ações fundamentais para o gerenciamento de alunos e logs de acesso.
 */
interface StudentRepositoryInterface
{
    public function getAll(): array;
    public function getById(int $id): ?Student;
    public function create(array $data): bool;
    public function update(int $id, array $data): bool;
    public function delete(int $id, ?string $operador = null): bool;
    public function deleteHistory(int $logId): bool;
    public function updateHistory(int $logId, string $newDate): bool;
    public function updateClass(int $id, string $className): bool;
    public function logAccess(int $studentId): bool;
    public function getDashboardStats(string $period = 'today', ?string $date = null, ?string $type = 'all'): array;
    public function getHistory(int $limit = 200, string $period = 'all', ?string $date = null, ?string $startDate = null, ?string $endDate = null): array;
    public function getTotalCount(): int;
    public function getClassDistribution(): array;
    public function listInactive(): array;
    public function activate(int $id): bool;
    public function getAllWithDescriptors(): array;
    public function updateBiometrics(int $id, array $data): bool;
}
