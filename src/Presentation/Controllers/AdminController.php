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
    public function create(array $data, string $currentAdminTipo = 'professor'): array
    {
        $usuario = trim($data['usuario'] ?? '');
        $senha   = trim($data['senha']   ?? '');
        $tipo    = in_array($data['tipo'] ?? '', ['admin', 'professor']) ? $data['tipo'] : 'professor';

        if ($currentAdminTipo !== 'admin') {
            return ['success' => false, 'message' => 'Apenas administradores podem cadastrar novos administradores ou professores.'];
        }

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
     * Admins podem resetar a senha de professores e de outros admins.
     * Professores só podem resetar a senha de outros professores e dele mesmo.
     */
    public function resetPassword(int $id, int $currentAdminId = 0, string $currentAdminTipo = 'professor', string $targetTipo = ''): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'ID inválido.'];

        $targetTipo = in_array($targetTipo, ['admin', 'professor']) ? $targetTipo : null;

        if (!$targetTipo) {
            // Determinar o tipo do usuário alvo (fallback automático)
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
        }

        if (!$targetTipo) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        // Aplicar as regras de permissão
        if ($currentAdminTipo === 'professor') {
            // Professor só reseta professor (outros ou ele mesmo)
            if ($targetTipo === 'admin') {
                return ['success' => false, 'message' => 'Um professor não possui permissão para resetar a senha de um administrador.'];
            }
        } elseif ($currentAdminTipo === 'admin') {
            // Admin reseta professores e OUTROS admins (não a si mesmo)
            if ($targetTipo === 'admin' && $id === $currentAdminId) {
                return ['success' => false, 'message' => 'Você não pode resetar sua própria senha administrativa.'];
            }
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
    public function delete(int $id, int $currentAdminId = 0, string $currentAdminTipo = 'professor', string $targetTipo = ''): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'ID inválido.'];

        // Se for professor, não pode excluir ninguém (nem admin, nem outro professor)
        if ($currentAdminTipo === 'professor') {
            return ['success' => false, 'message' => 'Professores não possuem permissão para excluir outros usuários.'];
        }

        $targetTipo = in_array($targetTipo, ['admin', 'professor']) ? $targetTipo : null;

        if (!$targetTipo) {
            // Determinar em qual tabela o usuário está cadastrado (fallback automático)
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

    /**
     * Importa professores a partir de um arquivo Excel/CSV/XLS.
     */
    public function importFromExcel(string $filePath): array
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet   = $spreadsheet->getActiveSheet();
            $rows        = $worksheet->toArray();
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erro ao processar a planilha: ' . $e->getMessage()
            ];
        }

        if (empty($rows)) {
            return ['success' => false, 'message' => 'A planilha está vazia ou não pôde ser lida.'];
        }

        // --- Detecção Dinâmica da Linha de Cabeçalho Real ---
        $headerRowIndex = -1;
        $maxHeaderScore = 0;
        
        $keywords = ['usuario', 'usuario_nome', 'user', 'username', 'nome', 'name', 'professor', 'teacher', 'docente'];
        
        $numRowsToTest = min(10, count($rows));
        for ($r = 0; $r < $numRowsToTest; $r++) {
            if (!isset($rows[$r]) || !is_array($rows[$r])) continue;
            
            $score = 0;
            foreach ($rows[$r] as $val) {
                if ($val === null) continue;
                $clean = strtolower(trim((string)$val));
                if ($clean === '') continue;
                
                foreach ($keywords as $kw) {
                    if (str_contains($clean, $kw)) {
                        $score++;
                    }
                }
            }
            
            if ($score > $maxHeaderScore) {
                $maxHeaderScore = $score;
                $headerRowIndex = $r;
            }
        }
        
        $userColIndex = -1;
        $startIndex = 0;
        
        if ($headerRowIndex !== -1 && $maxHeaderScore >= 1) {
            $header = $rows[$headerRowIndex];
            $startIndex = $headerRowIndex + 1;
            
            foreach ($header as $idx => $colName) {
                if ($colName === null) continue;
                $clean = strtolower(trim((string)$colName));
                foreach ($keywords as $kw) {
                    if ($clean === $kw || str_contains($clean, $kw)) {
                        $userColIndex = $idx;
                        break 2;
                    }
                }
            }
        }

        if ($userColIndex === -1) {
            $userColIndex = 0;
        }

        // Carregar professores existentes para evitar duplicidade
        $existingUsers = [];
        $resultQ = $this->db->query("SELECT LOWER(usuario) AS usuario FROM professores WHERE ativo = 1");
        if ($resultQ) {
            while ($row = $resultQ->fetch_assoc()) {
                $existingUsers[] = $row['usuario'];
            }
        }

        $imported = 0;
        $errors = 0;
        $duplicates = 0;

        $senhaPadrao = 'senaisp';
        $senhaHash = password_hash($senhaPadrao, PASSWORD_BCRYPT);

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (!isset($row[$userColIndex])) continue;
            $usuario = trim($row[$userColIndex]);
            if (empty($usuario)) continue;

            // Importar apenas o primeiro nome
            $parts = preg_split('/\s+/', $usuario);
            $primeiroNome = trim($parts[0] ?? '');
            if (empty($primeiroNome)) continue;

            $usuarioClean = $this->sanitizeUsername($primeiroNome);
            if (empty($usuarioClean)) continue;

            $usuarioLow = strtolower($usuarioClean);
            if (in_array($usuarioLow, $existingUsers)) {
                $duplicates++;
                continue;
            }

            $stmtIns = $this->db->prepare("INSERT INTO professores (usuario, senha_hash, senha_resetada) VALUES (?, ?, 2)");
            if ($stmtIns) {
                $stmtIns->bind_param("ss", $usuarioClean, $senhaHash);
                if ($stmtIns->execute()) {
                    $imported++;
                    $existingUsers[] = $usuarioLow;
                } else {
                    $errors++;
                }
                $stmtIns->close();
            } else {
                $errors++;
            }
        }

        if ($imported === 0 && $duplicates > 0) {
            return [
                'success' => false,
                'message' => 'Todos os professores desta planilha já estão cadastrados no sistema.',
                'imported' => 0,
                'errors' => $errors,
                'duplicates' => $duplicates
            ];
        }

        $msg = "Importação de professores concluída com sucesso!";
        if ($duplicates > 0) {
            $msg = "Importação concluída! {$imported} novos professores cadastrados ({$duplicates} já existiam).";
        }

        return [
            'success' => true,
            'message' => $msg,
            'imported' => $imported,
            'errors' => $errors,
            'duplicates' => $duplicates
        ];
    }

    private function sanitizeUsername(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        
        $str = preg_replace(
            ['/[áàâãä]/u', '/[éèêë]/u', '/[íìîï]/u', '/[óòôõö]/u', '/[úùûü]/u', '/ç/u'],
            ['a', 'e', 'i', 'o', 'u', 'c'],
            $str
        );

        $str = str_replace(' ', '.', $str);
        $str = preg_replace('/[^a-z0-9._\-@]/', '', $str);
        $str = preg_replace('/\.+/', '.', $str);
        return trim($str, '.');
    }
}
