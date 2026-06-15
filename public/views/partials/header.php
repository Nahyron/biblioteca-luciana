<?php
    $pageTitles = [
        'index' => 'Scanner de Acesso',
        'dashboard' => 'Painel de Fluxo',
        'students' => 'Gestão de Usuários',
        'history' => 'Histórico de Entradas',
        'classes' => 'Gestão de Turmas'
    ];
    $pageTitle = isset($currentPage) && isset($pageTitles[$currentPage]) ? $pageTitles[$currentPage] : 'Painel de Controle';
?>
<!-- CABEÇALHO DINÂMICO
     Exibe o título da seção atual e um relógio em tempo real.
-->
<header>
    <div style="display: flex; align-items: center; gap: 15px;">
        <button id="btn-menu-mobile" class="btn-menu-mobile" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
        <h2 id="page-title"><?php echo $pageTitle; ?></h2>
    </div>
    <div id="clock">00:00:00</div>
</header>

<!-- Cortina cinza para o menu mobile (aparece quando o menu abre no celular) -->
<div id="sidebar-overlay" onclick="toggleMobileMenu()"></div>
