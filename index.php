<?php
/**
 * PONTO DE ENTRADA DA RAIZ DO PROJETO
 * Redireciona o tráfego da raiz para a pasta pública (/public).
 * Caso o usuário esteja autenticado, redireciona diretamente para o painel principal.
 * Caso contrário, redireciona para a tela de login.
 */

// 1. Carrega as configurações globais e o carregador automático do PHP (Autoloader)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/autoload.php';

use App\Infrastructure\Auth\SessionAuth;

// 2. Inicia e verifica o estado de autenticação da sessão do usuário
if (SessionAuth::isAuthenticated()) {
    // Usuário logado: redireciona para o painel interno
    header('Location: public/controle.php');
} else {
    // Usuário não logado: redireciona para a tela de login
    header('Location: public/login.php');
}
exit;
