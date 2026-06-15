/**
 * Ponto de Entrada Principal (Main)
 * Orquestra o estado global e a inicialização de todos os módulos.
 */

// --- ESTADO GLOBAL ---
let flowChartInstance = null; // Instância do Chart.js
let currentChartType = 'line'; // Tipo ativo: line ou bar
let currentPeriod = 'today';   // Período: today, week, month
window.knownStudentsCache = null; // Cache de usuários para performance

// --- INICIALIZAÇÃO ---
document.addEventListener('DOMContentLoaded', () => {
    console.log('SISTEMA_BIBLIOTECA: Inicializando interface modular...');

    // Inicia o relógio digital (veja utils.js)
    if (typeof updateClock === 'function') {
        updateClock();
        setInterval(updateClock, 1000);
    }

    // Carregamento de dados vitais (Cache global usado pelo Scanner e Turmas)
    if (typeof loadStudents === 'function') {
        loadStudents();
    }
    
    if (document.getElementById('history-table-body') && typeof loadHistory === 'function') {
        loadHistory();
        if (typeof loadClassesFilter === 'function') {
            loadClassesFilter();
        }
    }
    
    if (document.getElementById('classes-grid') && typeof loadClasses === 'function') {
        loadClasses();
    }
    
    if (document.getElementById('flowChart') && typeof initCharts === 'function') {
        setTimeout(initCharts, 50);
    }
});
