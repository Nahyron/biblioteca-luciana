<?php
$currentPage = 'classes';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/autoload.php';
require_once SRC_PATH . '/Infrastructure/Auth/SessionAuth.php';
use App\Infrastructure\Auth\SessionAuth;
SessionAuth::requireAuth();
header("Cache-Control: no-cache, must-revalidate");

include_once PUBLIC_PATH . '/views/partials/head.php';
?>

<body>

    <!-- Navegação Lateral (Sidebar) -->
    <?php include_once PUBLIC_PATH . '/views/partials/sidebar.php'; ?>

    <!-- Área de Conteúdo Principal (Main) -->
    <main>
        <!-- Cabeçalho Dinâmico e Relógio -->
        <?php include_once PUBLIC_PATH . '/views/partials/header.php'; ?>

        <!-- Seção: Turmas -->
        <?php include_once PUBLIC_PATH . '/views/sections/classes.php'; ?>
    </main>

    <!-- Janelas Modais Requeridas -->
    <?php include_once PUBLIC_PATH . '/views/modals/class.php'; ?>
    <?php include_once PUBLIC_PATH . '/views/modals/manage_class.php'; ?>
    <?php include_once PUBLIC_PATH . '/views/modals/face-enrollment.php'; ?>  <!-- Wizard de cadastro biométrico via botão da tabela -->
    <?php include_once PUBLIC_PATH . '/views/modals/export_advanced.php'; ?> <!-- Disponível globalmente via menu -->

    <!-- Componentes Flutuantes (Toasts) -->
    <?php include_once PUBLIC_PATH . '/views/partials/toasts.php'; ?>

    <!-- Scripts -->
    <?php include_once PUBLIC_PATH . '/views/partials/scripts.php'; ?>

</body>
</html>
