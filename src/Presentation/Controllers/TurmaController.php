<?php

namespace App\Presentation\Controllers;

use App\Application\Services\TurmaService;

/**
 * Controller de Turmas.
 * Camada de Apresentação: Porta de entrada para requisições HTTP da API.
 */
class TurmaController
{
    public function __construct(private TurmaService $service) {}

    public function listAll(): array
    {
        return $this->service->listAll();
    }

    public function listInactive(): array
    {
        return $this->service->listInactive();
    }

    public function create(array $data): array
    {
        $nome   = $data['nome'] ?? '';
        $result = $this->service->create($nome);

        if ($result === true) {
            return ['success' => true, 'message' => 'Turma criada com sucesso!'];
        }

        // Tratamento de erro específico vindo do serviço/repositório
        $message = is_string($result) ? $result : 'Erro ao criar turma.';
        return ['success' => false, 'message' => $message];
    }

    /**
    /**
     * Desativa a turma (soft delete). Alunos desta turma também são desativados.
     */
    public function deactivate(int $id, ?string $operador = null): array
    {
        $success = $this->service->deactivate($id, $operador);
        return [
            'success' => $success,
            'message' => $success
                ? 'Turma desativada com sucesso! Alunos desta turma também foram desativados.'
                : 'Erro ao desativar turma.'
        ];
    }

    /**
     * Ativa uma turma desativada.
     */
    public function activate(int $id, ?string $operador = null): array
    {
        $success = $this->service->activate($id, $operador);
        return [
            'success' => $success,
            'message' => $success
                ? 'Turma reativada com sucesso! Alunos desta turma foram reativados.'
                : 'Erro ao reativar turma.'
        ];
    }

    /**
     * Alias para compatibilidade — delega para deactivate.
     */
    public function delete(int $id, ?string $operador = null): array
    {
        return $this->deactivate($id, $operador);
    }

    public function updateName(int $id, string $newName): array
    {
        $success = $this->service->updateName($id, $newName);
        return [
            'success' => $success,
            'message' => $success ? 'Nome da turma atualizado com sucesso!' : 'Erro ao atualizar turma.'
        ];
    }
}
