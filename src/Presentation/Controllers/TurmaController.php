<?php

namespace App\Presentation\Controllers;

use App\Application\Services\TurmaService;

/**
 * Controller de Turmas.
 * Camada de Apresentação: Porta de entrada para requisições HTTP da API.
 */
class TurmaController
{
    public function __construct(private TurmaService $service, private \mysqli $db) {}

    public function listAll(int $currentUserId = 0, string $currentUserTipo = 'professor'): array
    {
        $turmas = $this->service->listAll();
        foreach ($turmas as &$t) {
            $t['can_manage'] = \App\Infrastructure\Auth\SessionAuth::canManageClass($this->db, $currentUserId, $currentUserTipo, (int)$t['id']);
        }
        return $turmas;
    }

    public function listInactive(int $currentUserId = 0, string $currentUserTipo = 'professor'): array
    {
        $turmas = $this->service->listInactive();
        foreach ($turmas as &$t) {
            $t['can_manage'] = \App\Infrastructure\Auth\SessionAuth::canManageClass($this->db, $currentUserId, $currentUserTipo, (int)$t['id']);
        }
        return $turmas;
    }

    public function create(array $data, ?int $criadorId = null, ?string $criadorTipo = null): array
    {
        $nome   = $data['nome'] ?? '';
        $result = $this->service->create($nome, $criadorId, $criadorTipo);

        if ($result === true) {
            return ['success' => true, 'message' => 'Turma criada com sucesso!'];
        }

        // Tratamento de erro específico vindo do serviço/repositório
        $message = is_string($result) ? $result : 'Erro ao criar turma.';
        return ['success' => false, 'message' => $message];
    }

    /**
     * Desativa a turma (soft delete). Alunos desta turma também são desativados.
     */
    public function deactivate(int $id, ?string $operador = null, int $currentUserId = 0, string $currentUserTipo = 'professor'): array
    {
        $turma = $this->service->getById($id);
        if (!$turma) {
            return ['success' => false, 'message' => 'Turma não encontrada.'];
        }

        if ($currentUserTipo === 'professor') {
            if (!\App\Infrastructure\Auth\SessionAuth::canManageClass($this->db, $currentUserId, $currentUserTipo, $id)) {
                return ['success' => false, 'message' => 'Você não tem permissão para gerenciar esta turma, pois ela pertence a outro professor.'];
            }
        }

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
    public function activate(int $id, ?string $operador = null, int $currentUserId = 0, string $currentUserTipo = 'professor'): array
    {
        $turma = $this->service->getById($id);
        if (!$turma) {
            return ['success' => false, 'message' => 'Turma não encontrada.'];
        }

        if ($currentUserTipo === 'professor') {
            if (!\App\Infrastructure\Auth\SessionAuth::canManageClass($this->db, $currentUserId, $currentUserTipo, $id)) {
                return ['success' => false, 'message' => 'Você não tem permissão para gerenciar esta turma, pois ela pertence a outro professor.'];
            }
        }

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
    public function delete(int $id, ?string $operador = null, int $currentUserId = 0, string $currentUserTipo = 'professor'): array
    {
        return $this->deactivate($id, $operador, $currentUserId, $currentUserTipo);
    }

    public function updateName(int $id, string $newName, int $currentUserId = 0, string $currentUserTipo = 'professor'): array
    {
        $turma = $this->service->getById($id);
        if (!$turma) {
            return ['success' => false, 'message' => 'Turma não encontrada.'];
        }

        if ($currentUserTipo === 'professor') {
            if (!\App\Infrastructure\Auth\SessionAuth::canManageClass($this->db, $currentUserId, $currentUserTipo, $id)) {
                return ['success' => false, 'message' => 'Você não tem permissão para gerenciar esta turma, pois ela pertence a outro professor.'];
            }
        }

        $success = $this->service->updateName($id, $newName);
        return [
            'success' => $success,
            'message' => $success ? 'Nome da turma atualizado com sucesso!' : 'Erro ao atualizar turma.'
        ];
    }
}
