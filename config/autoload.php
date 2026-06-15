<?php

/**
 * Autoloader do projeto.
 * 
 * 1. Carrega o autoloader do Composer (vendor), que resolve todas as
 *    dependências externas (PhpSpreadsheet, DomPDF, etc.).
 * 2. Registra um autoloader manual para o namespace 'App\' (código do projeto).
 */

// 1. Autoloader do Composer (pacotes externos - DEVE vir primeiro)
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// 2. Autoloader manual do namespace App\
spl_autoload_register(function ($class) {
    // Prefixo do namespace do projeto
    $prefix = 'App\\';

    // Diretório base onde os arquivos fonte estão localizados
    $base_dir = __DIR__ . '/../src/';

    // Verifica se a classe utiliza o prefixo esperado
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Obtém o nome relativo da classe (sem o prefixo)
    $relative_class = substr($class, $len);

    // Substitui separadores de namespace por separadores de diretório e adiciona .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Se o arquivo existir, realiza o carregamento
    if (file_exists($file)) {
        require $file;
    }
});
