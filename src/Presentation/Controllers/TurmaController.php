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

    public function create(array $data): array
    {
        $nome = $data['nome'] ?? '';
        $result = $this->service->create($nome);

        if ($result === true) {
            return ['success' => true, 'message' => 'Turma criada com sucesso na nova arquitetura!'];
        }

        // Tratamento de erro específico vindo do serviço/repositório
        $message = is_string($result) ? $result : 'Erro ao criar turma.';
        if (str_contains($message, 'Duplicate entry')) {
            $message = 'Erro: Esta turma já existe no registro.';
        }

        return ['success' => false, 'message' => $message];
    }

    public function delete(int $id): array
    {
        $success = $this->service->delete($id);
        return [
            'success' => $success,
            'message' => $success 
                ? 'Turma excluída com sucesso e alunos desvinculados!' 
                : 'Erro ao deletar turma.'
        ];
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
