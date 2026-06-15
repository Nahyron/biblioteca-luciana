/**
 * Lógica de Navegação e Menu Mobile
 * Como o site agora é Multi-Page (MPA), a lógica do menu apenas exibe a barra lateral
 * e controla o modo responsivo.
 */


/**
 * Controle do menu hambúrguer para dispositivos móveis.
 */
function toggleMobileMenu() {
    const sidebar = document.querySelector('nav');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebar && overlay) {
        sidebar.classList.toggle('mobile-active');
        overlay.classList.toggle('active');
    }
}

// Configurar o fechamento do menu ao clicar em links (especialmente no mobile)
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nav-links li').forEach(li => {
        li.addEventListener('click', () => {
            if (window.innerWidth <= 768) toggleMobileMenu();
        });
    });
});
