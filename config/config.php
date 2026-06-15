<?php
// --- Checagem de Compatibilidade (PHP 8.2+) ---
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    die("Erro Crítico: Este sistema requer PHP 8.2 ou superior. Sua versão atual é: " . PHP_VERSION . ". Por favor, atualize o PHP no seu XAMPP/Servidor.");
}

/**
 * CONFIGURAÇÕES GLOBAIS (Cérebro da Aplicação)
 * 
 * Este arquivo centraliza todas as constantes do sistema, como nomes, 
 * URLs, fuso horário e chaves de APIs externas. Além de carregar o arquivo .env
 */

// --- Carregador de Arquivo de Configuração (.env) ---
function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . "=" . trim($value));
    }
}
loadEnv(dirname(__DIR__) . '/.env');

/**
 * Helper para obter variáveis de ambiente com valor padrão.
 */
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// --- Nomenclatura do Projeto ---
define('APP_NAME', 'SISTEMA_BIBLIOTECA');      // Nome oficial completo
define('APP_NAME_SHORT', 'BIBLIOTECA');       // Nome curto para a Logo

// --- Detecção Automática de URL (BASE_URL) ---
// Isso permite que o sistema funcione em qualquer PC/Pasta sem alterar código.
function getAutoBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Obtém o caminho do script atual
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $script_dir = str_replace('\\', '/', dirname($script_name));
    
    // Se o script estiver dentro de /public, removemos para pegar a raiz do projeto
    // Mas preservamos a subpasta se o projeto estiver em localhost/pasta-do-projeto
    $base_path = preg_replace('/\/public.*/', '', $script_dir);
    
    // Limpeza final para evitar barras duplicadas no final
    $base_path = rtrim($base_path, '/');
    
    return $protocol . "://" . $host . $base_path;
}
define('BASE_URL', env('APP_URL') ?: getAutoBaseUrl());

// --- Detecção de Pasta Pública na URL para Ativos e API ---
function checkPublicInUrl() {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    return (strpos(str_replace('\\', '/', $script_name), '/public') !== false);
}
define('PUBLIC_URL', checkPublicInUrl() ? BASE_URL . '/public' : BASE_URL);


// --- Mapeamento de Caminhos (Paths) ---
define('ROOT_PATH', realpath(dirname(__DIR__))); // Pasta pai (raiz do projeto)
define('BASE_DIR', ROOT_PATH);                   // Caminho físico raiz (Alias para ROOT_PATH)
define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public'); // Pasta de acesso público
define('SRC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'src');       // Pasta fonte PHP
define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage'); // Pasta para logs/uploads

// --- Variáveis de Branding ---
define('COLOR_PRIMARY', '#BC0000'); // Cor Institucional: Vermelho Carmim
define('COLOR_SECONDARY', '#FFFFFF'); // Cor Institucional: Branco Neve

// --- Regionalização (I18N) ---
date_default_timezone_set('America/Sao_Paulo');

