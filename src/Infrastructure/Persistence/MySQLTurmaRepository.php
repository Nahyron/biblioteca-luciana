<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\TurmaRepositoryInterface;
use App\Domain\Entities\Turma;
use mysqli;
use Exception;

/**
 * Implementação SQL da persistência de Turmas.
 * Camada de Infraestrutura: Conhece o Banco de Dados.
 */
class MySQLTurmaRepository implements TurmaRepositoryInterface
{
    private string $table = "turmas";

    public function __construct(private mysqli $db) {}

    /**
     * Retorna apenas turmas ATIVAS.
     */
    public function getAll(): array
    {
        $result = $this->db->query("SELECT id, nome, ativo, created_at FROM turmas WHERE ativo = 1 ORDER BY nome ASC");
        $turmas = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $turmas[] = new Turma($row['id'], $row['nome'], $row['created_at'], (int)$row['ativo']);
            }
        }
        return $turmas;
    }

    /**
     * Retorna apenas turmas INATIVAS.
     */
    public function getInactive(): array
    {
        $result = $this->db->query("SELECT id, nome, ativo, created_at FROM turmas WHERE ativo = 0 ORDER BY nome ASC");
        $turmas = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $turmas[] = new Turma($row['id'], $row['nome'], $row['created_at'], (int)$row['ativo']);
            }
        }
        return $turmas;
    }

    public function create(string $nome): bool|string
    {
        $stmt = $this->db->prepare("INSERT INTO turmas (nome, ativo) VALUES (?, 1)");
        if (!$stmt) return $this->db->error;

        $stmt->bind_param("s", $nome);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }

        $error = $stmt->error;
        $stmt->close();
        return $error;
    }

    /**
     * Desativa a turma (soft delete) em vez de excluir.
     * Alunos associados são desvinculados para 'Sem Turma'.
     */
    public function deactivate(int $id, ?string $operador = null): bool
    {
        // Pega o nome da turma
        $stmtName = $this->db->prepare("SELECT nome FROM turmas WHERE id = ?");
        if (!$stmtName) return false;
        $stmtName->bind_param("i", $id);
        $stmtName->execute();
        $result = $stmtName->get_result();

        if ($result->num_rows === 0) {
            $stmtName->close();
            return false;
        }

        $turma = $result->fetch_assoc();
        $nome  = $turma['nome'];
        $stmtName->close();

        // Trava de segurança: não permite desativar turmas de sistema
        if (in_array($nome, ['Sem Turma', 'N/A', 'Todas'])) {
            return false;
        }

        $this->db->begin_transaction();
        try {
            // Obter a lista de alunos da turma ANTES de desativar (para gravar o log no histórico)
            $studentIds = [];
            $stmtList = $this->db->prepare("SELECT id FROM usuarios WHERE turma = ?");
            if ($stmtList) {
                $stmtList->bind_param("s", $nome);
                $stmtList->execute();
                $resList = $stmtList->get_result();
                while ($row = $resList->fetch_assoc()) {
                    $studentIds[] = (int)$row['id'];
                }
                $stmtList->close();
            }

            // Desativa os alunos dessa turma (muda status para inativo)
            $stmtStudents = $this->db->prepare("UPDATE usuarios SET status = 'inativo' WHERE turma = ?");
            if ($stmtStudents) {
                $stmtStudents->bind_param("s", $nome);
                $stmtStudents->execute();
                $stmtStudents->close();
            }

            // Grava o log de desativação para cada aluno no histórico (acessos_log)
            if (!empty($studentIds)) {
                $stmtLog = $this->db->prepare("INSERT INTO acessos_log (usuario_id, acao, operador) VALUES (?, 'desativacao', ?)");
                if ($stmtLog) {
                    foreach ($studentIds as $uid) {
                        $stmtLog->bind_param("is", $uid, $operador);
                        $stmtLog->execute();
                    }
                    $stmtLog->close();
                }
            }

            // Desativa a turma (soft delete)
            $stmtDeactivate = $this->db->prepare("UPDATE turmas SET ativo = 0 WHERE id = ?");
            if ($stmtDeactivate) {
                $stmtDeactivate->bind_param("i", $id);
                $stmtDeactivate->execute();
                $stmtDeactivate->close();
            }

            // Grava o log da desativação da turma em turmas_log
            $stmtTurmaLog = $this->db->prepare("INSERT INTO turmas_log (turma_nome, acao, operador) VALUES (?, 'desativacao_turma', ?)");
            if ($stmtTurmaLog) {
                $stmtTurmaLog->bind_param("ss", $nome, $operador);
                $stmtTurmaLog->execute();
                $stmtTurmaLog->close();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Ativa uma turma previamente desativada e seus respectivos alunos.
     */
    public function activate(int $id, ?string $operador = null): bool
    {
        // Pega o nome da turma para ativar os alunos correspondentes
        $stmtName = $this->db->prepare("SELECT nome FROM turmas WHERE id = ?");
        if (!$stmtName) return false;
        $stmtName->bind_param("i", $id);
        $stmtName->execute();
        $resultName = $stmtName->get_result();

        if ($resultName->num_rows === 0) {
            $stmtName->close();
            return false;
        }

        $turma = $resultName->fetch_assoc();
        $nome  = $turma['nome'];
        $stmtName->close();

        $this->db->begin_transaction();
        try {
            // Ativa a turma
            $stmt = $this->db->prepare("UPDATE turmas SET ativo = 1 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }

            // Ativa os alunos associados a essa turma
            $stmtStudents = $this->db->prepare("UPDATE usuarios SET status = 'ativo' WHERE turma = ?");
            if ($stmtStudents) {
                $stmtStudents->bind_param("s", $nome);
                $stmtStudents->execute();
                $stmtStudents->close();
            }

            // Obter os IDs de alunos ativados para registrar no log do histórico
            $studentIds = [];
            $stmtList = $this->db->prepare("SELECT id FROM usuarios WHERE turma = ?");
            if ($stmtList) {
                $stmtList->bind_param("s", $nome);
                $stmtList->execute();
                $resList = $stmtList->get_result();
                while ($row = $resList->fetch_assoc()) {
                    $studentIds[] = (int)$row['id'];
                }
                $stmtList->close();
            }

            // Grava o log de ativação para cada aluno no histórico (acessos_log)
            if (!empty($studentIds)) {
                $stmtLog = $this->db->prepare("INSERT INTO acessos_log (usuario_id, acao, operador) VALUES (?, 'ativacao', ?)");
                if ($stmtLog) {
                    foreach ($studentIds as $uid) {
                        $stmtLog->bind_param("is", $uid, $operador);
                        $stmtLog->execute();
                    }
                    $stmtLog->close();
                }
            }

            // Grava o log da reativação da turma em turmas_log
            $stmtTurmaLog = $this->db->prepare("INSERT INTO turmas_log (turma_nome, acao, operador) VALUES (?, 'ativacao_turma', ?)");
            if ($stmtTurmaLog) {
                $stmtTurmaLog->bind_param("ss", $nome, $operador);
                $stmtTurmaLog->execute();
                $stmtTurmaLog->close();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Mantido para compatibilidade com a interface — delega para deactivate.
     */
    public function delete(int $id, ?string $operador = null): bool
    {
        return $this->deactivate($id, $operador);
    }

    public function updateName(int $id, string $newName): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET nome = ? WHERE id = ?");
        if (!$stmt) return false;

        $stmt->bind_param("si", $newName, $id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function getByName(string $nome): ?Turma
    {
        $stmt = $this->db->prepare("SELECT id, nome, ativo, created_at FROM turmas WHERE nome = ? AND ativo = 1 LIMIT 1");
        if (!$stmt) return null;

        $stmt->bind_param("s", $nome);
        $stmt->execute();
        $result = $stmt->get_result();
        $data   = $result->fetch_assoc();
        $stmt->close();

        return $data ? new Turma($data['id'], $data['nome'], $data['created_at'], (int)$data['ativo']) : null;
    }
}
