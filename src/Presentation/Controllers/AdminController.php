<?php

namespace App\Presentation\Controllers;

use mysqli;

/**
 * CONTROLLER DE ADMINISTRADORES E PROFESSORES
 *
 * Gerencia as operações CRUD para as tabelas `admins` e `professores`.
 */
class AdminController
{
    public function __construct(private mysqli $db) {}

    /**
     * Lista todos os usuários de um tipo específico (admin ou professor).
     */
    public function listAll(string $tipo = 'admin'): array
    {
        $tipo = in_array($tipo, ['admin', 'professor']) ? $tipo : 'admin';
        $table = $tipo === 'admin' ? 'admins' : 'professores';

        $stmt = $this->db->prepare(
            "SELECT id, usuario, '{$tipo}' AS tipo, criado_at
             FROM {$table}
             WHERE ativo = 1
             ORDER BY criado_at DESC"
        );
        if (!$stmt) return [];

        $stmt->execute();
        $result = $stmt->get_result();
        $data   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    /**
     * Cadastra um novo administrador ou professor.
     */
    public function create(array $data): array
    {
        $usuario = trim($data['usuario'] ?? '');
        $senha   = trim($data['senha']   ?? '');
        $tipo    = in_array($data['tipo'] ?? '', ['admin', 'professor']) ? $data['tipo'] : 'professor';

        if (empty($usuario) || empty($senha)) {
            return ['success' => false, 'message' => 'Usuário e senha são obrigatórios.'];
        }

        if (!preg_match('/^[A-Za-z0-9._\-@]+$/', $usuario)) {
            return ['success' => false, 'message' => 'Usuário inválido. Use apenas letras, números, ponto, hífen ou @.'];
        }

        if (strlen($senha) < 4) {
            return ['success' => false, 'message' => 'A senha deve ter no mínimo 4 caracteres.'];
        }

        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
        $table = $tipo === 'admin' ? 'admins' : 'professores';

        $stmt = $this->db->prepare("INSERT INTO {$table} (usuario, senha_hash) VALUES (?, ?)");
        if (!$stmt) return ['success' => false, 'message' => 'Erro interno ao preparar cadastro.'];

        $stmt->bind_param("ss", $usuario, $senhaHash);
        $result = $stmt->execute();
        $errno  = $this->db->errno;
        $stmt->close();

        if (!$result) {
            if ($errno === 1062) {
                return ['success' => false, 'message' => "O usuário \"{$usuario}\" já está cadastrado no sistema."];
            }
            return ['success' => false, 'message' => 'Erro ao cadastrar. Tente novamente.'];
        }

        $tipoLabel = $tipo === 'admin' ? 'Administrador' : 'Professor';
        return ['success' => true, 'message' => "{$tipoLabel} \"{$usuario}\" cadastrado com sucesso!"];
    }

    /**
     * Redefine a senha de um usuário para a senha padrão do sistema ('senaisp').
     * Professores podem resetar a senha de outros professores, mas não de admins.
     */
    public function resetPassword(int $id, string $currentAdminTipo = 'professor'): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'ID inválido.'];

        // Determinar o tipo do usuário alvo
        $targetTipo = null;
        
        // Verifica se o ID existe na tabela 'admins'
        $stmt = $this->db->prepare("SELECT id FROM admins WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $targetTipo = 'admin';
        }
        $stmt->close();

        // Se não encontrou em admins, verifica em professores
        if (!$targetTipo) {
            $stmt = $this->db->prepare("SELECT id FROM professores WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $targetTipo = 'professor';
            }
            $stmt->close();
        }

        if (!$targetTipo) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        // Aplicar a regra de permissão
        if ($currentAdminTipo === 'professor' && $targetTipo === 'admin') {
            return ['success' => false, 'message' => 'Um professor não pode resetar a senha de um administrador.'];
        }

        $novaSenha = 'senaisp';
        $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);
        $table = $targetTipo === 'admin' ? 'admins' : 'professores';

        $stmt = $this->db->prepare("UPDATE {$table} SET senha_hash = ?, senha_resetada = 2 WHERE id = ?");
        if (!$stmt) return ['success' => false, 'message' => 'Erro interno ao preparar reset.'];

        $stmt->bind_param("si", $senhaHash, $id);
        $result   = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if (!$result || $affected === 0) {
            return ['success' => false, 'message' => 'Usuário não encontrado ou nenhuma alteração realizada.'];
        }

        return ['success' => true, 'message' => 'Senha redefinida para "senaisp" com sucesso!'];
    }

    /**
     * Exclui permanentemente um usuário pelo ID.
     * Impede que professores excluam outros usuários (seja professor ou admin).
     * Impede auto-exclusão para admins.
     */
    public function delete(int $id, int $currentAdminId = 0, string $currentAdminTipo = 'professor'): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'ID inválido.'];

        // Se for professor, não pode excluir ninguém (nem admin, nem outro professor)
        if ($currentAdminTipo === 'professor') {
            return ['success' => false, 'message' => 'Professores não possuem permissão para excluir outros usuários.'];
        }

        // Determinar em qual tabela o usuário está cadastrado
        $targetTipo = null;
        
        $stmt = $this->db->prepare("SELECT id FROM admins WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $targetTipo = 'admin';
        }
        $stmt->close();

        if (!$targetTipo) {
            $stmt = $this->db->prepare("SELECT id FROM professores WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $targetTipo = 'professor';
            }
            $stmt->close();
        }

        if (!$targetTipo) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        // Impede auto-exclusão
        if ($targetTipo === 'admin' && $id === $currentAdminId) {
            return ['success' => false, 'message' => 'Você não pode excluir sua própria conta.'];
        }

        $table = $targetTipo === 'admin' ? 'admins' : 'professores';
        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE id = ?");
        if (!$stmt) return ['success' => false, 'message' => 'Erro interno ao preparar exclusão.'];

        $stmt->bind_param("i", $id);
        $result   = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if (!$result || $affected === 0) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        return ['success' => true, 'message' => 'Usuário excluído com sucesso!'];
    }

    /**
     * Altera a senha do próprio usuário logado (usado quando a troca de senha é obrigatória).
     */
    public function changeOwnPassword(int $id, string $tipo, string $novaSenha): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'ID de usuário inválido.'];
        if (strlen(trim($novaSenha)) < 4) {
            return ['success' => false, 'message' => 'A nova senha deve ter no mínimo 4 caracteres.'];
        }

        $tipo = in_array($tipo, ['admin', 'professor']) ? $tipo : 'professor';
        $table = $tipo === 'admin' ? 'admins' : 'professores';
        $senhaHash = password_hash(trim($novaSenha), PASSWORD_BCRYPT);

        $stmt = $this->db->prepare("UPDATE {$table} SET senha_hash = ?, senha_resetada = 0 WHERE id = ?");
        if (!$stmt) return ['success' => false, 'message' => 'Erro interno ao preparar alteração.'];

        $stmt->bind_param("si", $senhaHash, $id);
        $result = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if (!$result || $affected === 0) {
            return ['success' => false, 'message' => 'Usuário não encontrado ou nova senha idêntica à anterior.'];
        }

        return ['success' => true, 'message' => 'Senha alterada com sucesso!'];
    }
}
