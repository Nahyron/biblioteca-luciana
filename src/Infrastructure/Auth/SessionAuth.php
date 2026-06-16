<?php

/**
 * Autenticação Simples por Sessão PHP
 * Gerencia login/logout de administradores do sistema.
 */

namespace App\Infrastructure\Auth;

class SessionAuth
{
    private const SESSION_KEY = 'biblioteca_admin';

    /**
     * Inicia a sessão se ainda não iniciada.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verifica se há um administrador autenticado na sessão.
     */
    public static function isAuthenticated(): bool
    {
        self::start();
        return isset($_SESSION[self::SESSION_KEY]) && $_SESSION[self::SESSION_KEY] === true;
    }

    /**
     * Redireciona para login se não autenticado.
     */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Registra o administrador como autenticado e armazena seu ID na sessão.
     */
    public static function login(int $adminId = 0, string $adminTipo = 'professor', bool $forcePasswordChange = false): void
    {
        self::start();
        $_SESSION[self::SESSION_KEY] = true;
        if ($adminId > 0) {
            $_SESSION['biblioteca_admin_id'] = $adminId;
        }
        $_SESSION['biblioteca_admin_tipo'] = $adminTipo;
        $_SESSION['biblioteca_force_password_change'] = $forcePasswordChange;
    }

    /**
     * Retorna o ID do administrador atualmente autenticado.
     */
    public static function getAdminId(): int
    {
        self::start();
        return (int)($_SESSION['biblioteca_admin_id'] ?? 0);
    }

    /**
     * Retorna o tipo do administrador atualmente autenticado ('admin' ou 'professor').
     */
    public static function getAdminTipo(): string
    {
        self::start();
        return $_SESSION['biblioteca_admin_tipo'] ?? 'professor';
    }

    /**
     * Verifica se o usuário logado deve ser obrigado a trocar de senha.
     */
    public static function shouldForcePasswordChange(): bool
    {
        self::start();
        return (bool)($_SESSION['biblioteca_force_password_change'] ?? false);
    }

    /**
     * Altera o status da obrigação de troca de senha na sessão.
     */
    public static function setForcePasswordChange(bool $force): void
    {
        self::start();
        $_SESSION['biblioteca_force_password_change'] = $force;
    }

    /**
     * Encerra a sessão e faz logout.
     */
    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Verifica se um professor tem permissão para gerenciar uma determinada turma.
     * Administradores sempre têm permissão total.
     * Professores têm permissão se forem os criadores da turma ou se estiverem na tabela turma_professor.
     */
    public static function canManageClass(\mysqli $db, int $userId, string $userTipo, int $turmaId): bool
    {
        if ($userTipo === 'admin') {
            return true;
        }

        // 1. Verifica se o professor é o criador original
        $stmt = $db->prepare("SELECT criador_id, criador_tipo FROM turmas WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $turmaId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                if ($row['criador_tipo'] === 'professor' && (int)$row['criador_id'] === $userId) {
                    $stmt->close();
                    return true;
                }
            }
            $stmt->close();
        }

        // 2. Verifica se há permissão delegada na tabela turma_professor
        $stmtPerm = $db->prepare("SELECT 1 FROM turma_professor WHERE turma_id = ? AND professor_id = ? LIMIT 1");
        if ($stmtPerm) {
            $stmtPerm->bind_param("ii", $turmaId, $userId);
            $stmtPerm->execute();
            $resPerm = $stmtPerm->get_result();
            $hasPerm = ($resPerm && $resPerm->num_rows > 0);
            $stmtPerm->close();
            return $hasPerm;
        }

        return false;
    }

    /**
     * Verifica se um professor tem permissão para gerenciar uma turma pelo nome da turma.
     */
    public static function canManageClassByName(\mysqli $db, int $userId, string $userTipo, string $turmaNome): bool
    {
        if ($userTipo === 'admin') {
            return true;
        }

        if (in_array($turmaNome, ['Sem Turma', 'N/A', 'N/A '])) {
            return false;
        }

        // Busca o ID da turma pelo nome
        $stmtId = $db->prepare("SELECT id FROM turmas WHERE nome = ? LIMIT 1");
        if ($stmtId) {
            $stmtId->bind_param("s", $turmaNome);
            $stmtId->execute();
            $resId = $stmtId->get_result();
            if ($resId && $rowId = $resId->fetch_assoc()) {
                $turmaId = (int)$rowId['id'];
                $stmtId->close();
                return self::canManageClass($db, $userId, $userTipo, $turmaId);
            }
            $stmtId->close();
        }

        return false;
    }
}
