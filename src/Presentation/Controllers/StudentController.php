<?php

namespace App\Presentation\Controllers;

use App\Application\Services\StudentService;

/**
 * Controller de Alunos e Acessos.
 * Camada de Apresentação: Processa requisições HTTP para Alunos.
 */
class StudentController
{
    public function __construct(private StudentService $service) {}

    public function listAll(): array
    {
        return $this->service->listAll();
    }

    public function register(array $data): array
    {
        return $this->service->register($data);
    }

    public function recordAccess(int $studentId): array
    {
        $success = $this->service->recordAccess($studentId);
        return [
            'success' => $success,
            'message' => $success ? 'Acesso registrado com sucesso (Clean Arch)!' : 'Erro ao registrar acesso.'
        ];
    }

    public function delete(int $id, ?string $operador = null): array
    {
        $success = $this->service->delete($id, $operador);
        return [
            'success' => $success,
            'message' => $success ? 'Usuário desativado com sucesso!' : 'Erro ao desativar aluno.'
        ];
    }

    public function listInactive(): array
    {
        $students = $this->service->listInactive();
        return $students; // returns array of inactive students
    }

    public function activate(int $id): array
    {
        $success = $this->service->activate($id);
        return [
            'success' => $success,
            'message' => $success ? 'Usuário reativado com sucesso!' : 'Erro ao reativar usuário.'
        ];
    }
    public function deleteHistory(int $logId): array
    {
        $success = $this->service->deleteHistory($logId);
        return [
            'success' => $success,
            'message' => $success ? 'Registro de acesso apagado!' : 'Erro ao excluir histórico.'
        ];
    }

    public function updateName(int $id, string $newName): array
    {
        $success = $this->service->updateName($id, $newName);
        return [
            'success' => $success,
            'message' => $success ? 'Nome atualizado via Editor!' : 'Erro na atualização.'
        ];
    }

    public function updateHistory(int $logId, string $newDate): array
    {
        $success = $this->service->updateHistory($logId, $newDate);
        return [
            'success' => $success,
            'message' => $success ? 'Registro de acesso modificado!' : 'Erro na modificação.'
        ];
    }

    public function updateClass(int $id, string $className): array
    {
        $success = $this->service->updateClass($id, $className);
        return [
            'success' => $success,
            'message' => $success ? 'Turma do aluno atualizada com sucesso!' : 'Erro ao atualizar turma.'
        ];
    }

    public function getDashboardStats(string $period = 'today', ?string $date = null, ?string $type = 'all'): array
    {
        return $this->service->getDashboardStats($period, $date, $type);
    }

    public function listHistory(string $period = 'all', ?string $date = null, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->service->listHistory($period, $date, $startDate, $endDate);
    }

    public function updateBiometrics(int $id, array $data): array
    {
        $success = $this->service->updateBiometrics($id, $data);
        return [
            'success' => $success,
            'message' => $success ? 'Dados biométricos atualizados!' : 'Erro ao atualizar biometria.'
        ];
    }

    public function importFromExcel(string $filePath, string $className): array
    {
        return $this->service->importFromExcel($filePath, $className);
    }
}
