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
    $stmt = $db->prepare("SELECT senha_hash FROM admins WHERE usuario = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $res   = $stmt->get_result();
    $admin = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($admin && password_verify($senha, $admin['senha_hash'])) {
        SessionAuth::login();
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

