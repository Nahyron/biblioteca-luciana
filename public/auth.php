<?php
/**
 * ENDPOINT DE AUTENTICAÇÃO DO ADMINISTRADOR
 * Processa login via POST e redireciona conforme resultado.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/autoload.php';
require_once SRC_PATH . '/Infrastructure/Auth/SessionAuth.php';

use App\Config\Database;
use App\Infrastructure\Auth\SessionAuth;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$usuario = preg_replace('/[^A-Za-z0-9\._\-@]/', '', trim($_POST['usuario'] ?? ''));
$senha   = trim($_POST['senha'] ?? '');

if (empty($usuario) || empty($senha)) {
    header('Location: login.php?erro=campos_vazios');
    exit;
}

try {
    $db   = (new Database())->getConnection();
    
    // 1. Tenta buscar na tabela 'admins'
    $stmt = $db->prepare("SELECT id, senha_hash, senha_resetada FROM admins WHERE usuario = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $res   = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    
    $tipo = 'admin';

    // 2. Se não encontrou na tabela 'admins', busca na tabela 'professores'
    if (!$user) {
        $stmt = $db->prepare("SELECT id, senha_hash, senha_resetada FROM professores WHERE usuario = ? AND ativo = 1 LIMIT 1");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $res   = $stmt->get_result();
        $user = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        $tipo = 'professor';
    }

    if ($user && password_verify($senha, $user['senha_hash'])) {
        $senhaResetada = (int)($user['senha_resetada'] ?? 0);
        $force = false;

        if ($senhaResetada === 2) {
            // Primeira entrada: decrementa para 1 e não força a alteração ainda
            $table = $tipo === 'admin' ? 'admins' : 'professores';
            $stmtUpdate = $db->prepare("UPDATE {$table} SET senha_resetada = 1 WHERE id = ?");
            $stmtUpdate->bind_param("i", $user['id']);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        } elseif ($senhaResetada === 1) {
            // Segunda entrada: força a troca de senha
            $force = true;
        }

        SessionAuth::login((int)($user['id'] ?? 0), $tipo, $force);
        header('Location: controle.php');
        exit;
    } else {
        header('Location: login.php?erro=credenciais_invalidas');
        exit;
    }
} catch (Throwable $e) {
    header('Location: login.php?erro=erro_servidor');
    exit;
}

