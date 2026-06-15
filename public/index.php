<?php
/**
 * PÁGINA PRINCIPAL E SCANNER - SISTEMA DE BIBLIOTECA (VISÃO COMPUTACIONAL)
 * -------------------------------------------------------------------
 * Modelo Multi-Page (MPA). Esta página foca APENAS na câmera e reconhecimento.
 */
$currentPage = 'index';
// Configurações e Cache
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/autoload.php';
require_once SRC_PATH . '/Infrastructure/Auth/SessionAuth.php';
use App\Infrastructure\Auth\SessionAuth;
SessionAuth::requireAuth();
header("Cache-Control: no-cache, must-revalidate");

// 1. Cabeçalho HTML, Meta Tags e CDNs
include_once PUBLIC_PATH . '/views/partials/head.php';
?>

<body>

    <!-- 2. Navegação Lateral (Sidebar) -->
    <?php include_once PUBLIC_PATH . '/views/partials/sidebar.php'; ?>

    <!-- 3. Área de Conteúdo Principal (Main) -->
    <main>
        <!-- Cabeçalho Dinâmico e Relógio -->
        <?php include_once PUBLIC_PATH . '/views/partials/header.php'; ?>

        <!-- Seções de Conteúdo (MPA - APENAS CÂMERA AQUI) -->
        <?php include_once PUBLIC_PATH . '/views/sections/vision.php'; ?>
    </main>

    <!-- 4. Janelas Modais Requeridas (Apenas as usadas no Scanner Global) -->
    <?php 
        include_once PUBLIC_PATH . '/views/modals/register.php';           // Acionado via botão no Scanner
        include_once PUBLIC_PATH . '/views/modals/face-enrollment.php';    // Wizard de captura 3 ângulos
        include_once PUBLIC_PATH . '/views/modals/export_advanced.php';    // Acionado globalmente via Sidebar
        include_once PUBLIC_PATH . '/views/modals/camera-selector.php';    // Selecionador de câmera
    ?>

    <!-- 5. Componentes Flutuantes (Toasts) -->
    <?php include_once PUBLIC_PATH . '/views/partials/toasts.php'; ?>

    <!-- 6. Scripts e Lógica (Arquitetura Modular em JS) -->
    <?php include_once PUBLIC_PATH . '/views/partials/scripts.php'; ?>

</body>
</html>