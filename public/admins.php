<?php
/**
 * PÁGINA: GERENCIAMENTO DE ADMINISTRADORES E PROFESSORES
 * Acessível apenas para usuários autenticados (sessão ativa).
 * Permite cadastrar admins e professores, resetar senhas e excluir usuários.
 */
$currentPage = 'admins';
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

        <!-- Seção: Gerenciamento de Admins e Professores -->
        <?php include_once PUBLIC_PATH . '/views/sections/admins.php'; ?>
    </main>

    <!-- Componentes Flutuantes (Toasts) -->
    <?php include_once PUBLIC_PATH . '/views/partials/toasts.php'; ?>

    <!-- Modais utilitários (confirm, edit) reutilizados das outras páginas -->
    <?php include_once PUBLIC_PATH . '/views/modals/register.php'; ?>

    <!-- Scripts e Lógica Modular -->
    <?php include_once PUBLIC_PATH . '/views/partials/scripts.php'; ?>

</body>
</html>
