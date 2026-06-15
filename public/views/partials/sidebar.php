<!-- MENU LATERAL (Sidebar)
     Contém a logo institucional e os links de navegação por seções. 
-->
<nav>
    <div class="sidebar-header">
        <h1>SISTEMA</h1>
        <div class="senai-logo"><?php echo APP_NAME_SHORT; ?></div>
    </div>
    <ul class="nav-links">
        <li class="<?php echo (isset($currentPage) && $currentPage === 'controle') ? 'active' : ''; ?>" onclick="window.location.href='controle.php'">
            <i class="fas fa-camera"></i> Reconhecimento
        </li>
        <li class="<?php echo (isset($currentPage) && $currentPage === 'dashboard') ? 'active' : ''; ?>" onclick="window.location.href='dashboard.php'">
            <i class="fas fa-chart-line"></i> Dashboard
        </li>
        <li class="<?php echo (isset($currentPage) && $currentPage === 'students') ? 'active' : ''; ?>" onclick="window.location.href='students.php'">
            <i class="fas fa-users"></i> Alunos
        </li>
        <li class="<?php echo (isset($currentPage) && $currentPage === 'classes') ? 'active' : ''; ?>" onclick="window.location.href='classes.php'">
            <i class="fas fa-layer-group"></i> Turmas
        </li>
        <li class="<?php echo (isset($currentPage) && $currentPage === 'history') ? 'active' : ''; ?>" onclick="window.location.href='history.php'">
            <i class="fas fa-history"></i> Histórico
        </li>
        <li class="<?php echo (isset($currentPage) && $currentPage === 'totem') ? 'active' : ''; ?>" onclick="window.location.href='totem.php'">
            <i class="fas fa-expand"></i> Reconhecimento de Aluno
        </li>
        <li onclick="openExportModal()">
            <i class="fas fa-file-excel"></i> Exportar
        </li>
        <li class="<?php echo (isset($currentPage) && $currentPage === 'admins') ? 'active' : ''; ?>" onclick="window.location.href='admins.php'">
            <i class="fas fa-user-shield"></i> Administradores
        </li>
    </ul>

    <!-- Botão de Logout -->
    <div style="padding: 1.2rem 1.5rem; border-top: none !important; margin-top: auto;">
        <a href="logout.php"
           style="display: flex; align-items: center; justify-content: center; gap: 0.7rem; color: #ffffff; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: none; padding: 0.75rem 1rem; border-radius: 6px; transition: all 0.25s ease;"
           onmouseover="this.style.background='rgba(255, 255, 255, 0.18)'; this.style.borderColor='rgba(255, 255, 255, 0.3)'; this.style.boxShadow='0 0 10px rgba(255, 255, 255, 0.1)';"
           onmouseout="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.borderColor='rgba(255, 255, 255, 0.15)'; this.style.boxShadow='none';">
            <i class="fas fa-door-open"></i> Sair do Sistema
        </a>
    </div>
</nav>
