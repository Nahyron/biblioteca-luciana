<!-- SEÇÃO 4: HISTÓRICO GERAL
     Log completo de todas as entradas registradas no sistema.
-->
<section id="sec-history">
    <div class="card">
        <!-- Cabeçalho com ações -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <h3 style="margin: 0;">Audit de Acessos Recentes</h3>
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <!-- Barra de Pesquisa por Nome -->
                <div style="position: relative;">
                    <i class="fas fa-user" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #aaa; pointer-events: none;"></i>
                    <input type="text" id="history-search-name" oninput="searchHistory()" placeholder="Buscar aluno..." style="padding: 0.5rem 1rem 0.5rem 2.2rem; border: 1px solid var(--gray-border, #ddd); border-radius: 8px; font-size: 0.9rem; width: 180px;">
                </div>
                <!-- Barra de Pesquisa por Turma -->
                <div style="position: relative;">
                    <i class="fas fa-graduation-cap" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #aaa; pointer-events: none;"></i>
                    <input type="text" id="history-search-class" oninput="searchHistory()" placeholder="Buscar turma..." style="padding: 0.5rem 1rem 0.5rem 2.2rem; border: 1px solid var(--gray-border, #ddd); border-radius: 8px; font-size: 0.9rem; width: 140px;">
                </div>
                <!-- Filtro de Período -->
                <select id="history-period-filter" onchange="filterHistoryAndExport()" style="padding: 0.5rem 1rem; border: 1px solid var(--gray-border, #ddd); border-radius: 8px; font-size: 0.9rem; cursor: pointer;">
                    <option value="all">Todos os registros</option>
                    <option value="today">Hoje</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mês</option>
                </select>
                <!-- Data customizada -->
                <input type="date" id="history-date-filter" onchange="filterHistoryAndExport()" style="padding: 0.5rem 0.75rem; border: 1px solid var(--gray-border, #ddd); border-radius: 8px; font-size: 0.9rem;">
                <!-- Botão Exportar XLS -->
                <button class="btn-primary" onclick="exportHistoryXLS()" style="padding: 0.5rem 1.2rem; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; background-color: #1d6f42; border-color: #1d6f42;">
                    <i class="fas fa-file-excel"></i> Exportar XLS
                </button>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#ID Log</th>
                        <th>Nome</th>
                        <th>Turma</th>
                        <th>Carimbo de Tempo (Data/Hora)</th>
                    </tr>
                </thead>
                <tbody id="history-table-body">
                    <!-- Popula via AJAX (history.js) -->
                </tbody>
            </table>
        </div>
    </div>
</section>

