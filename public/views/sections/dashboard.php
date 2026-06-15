<!-- SEÇÃO 2: DASHBOARD (Estatísticas)
     Exibe gráficos de fluxo e contagem de presença.
-->
<section id="sec-dashboard">
    <div class="dashboard-grid">
        <div class="card chart-card">
            <div class="card-header">
                <h3>Fluxo Horário (Frequência)</h3>
                <div class="chart-controls" style="display: flex; gap: 8px; align-items: center;">
                    <input type="date" id="dashboard-date-picker" onchange="changeDashboardDate(this.value)" 
                        style="padding: 6px 10px; font-size: 0.85rem; border: 1px solid #ccc; border-radius: 4px; outline: none; background-color: #fff; cursor: pointer; transition: border-color 0.2s; display: none;" />
                    <button id="btn-type-line" class="btn-secondary active"
                        onclick="changeChartType('line')">Linha</button>
                    <button id="btn-type-bar" class="btn-secondary"
                        onclick="changeChartType('bar')">Barra</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="flowChart"></canvas>
            </div>
        </div>

        <div class="card stats-card">
            <div class="card-header" style="margin-bottom: 1rem;">
                <h3 id="stat-period-title">Acessos Hoje</h3>
            </div>

            <div class="total-acessos-number" id="total-acessos" style="margin-bottom: 1rem;">0</div>

            <!-- Filtro de Tipo de Aluno (Biometria) -->
            <div class="type-selector" style="display: flex; gap: 5px; margin-bottom: 1rem; background: rgba(0, 0, 0, 0.05); padding: 4px; border-radius: 6px;">
                <button id="btn-type-all" class="btn-secondary btn-sm active" onclick="changeAccessType('all')"
                    style="flex: 1; padding: 5px; font-size: 0.72rem; border-radius: 4px; border: none; font-weight: 500; cursor: pointer;">Todos</button>
                <button id="btn-type-registered" class="btn-secondary btn-sm" onclick="changeAccessType('registered')"
                    style="flex: 1; padding: 5px; font-size: 0.72rem; border-radius: 4px; border: none; font-weight: 500; cursor: pointer;">Ativos</button>
                <button id="btn-type-unregistered" class="btn-secondary btn-sm" onclick="changeAccessType('unregistered')"
                    style="flex: 1; padding: 5px; font-size: 0.72rem; border-radius: 4px; border: none; font-weight: 500; cursor: pointer;">Inativos</button>
            </div>

            <div class="period-selector" style="display: flex; gap: 5px; margin-bottom: 1.5rem;">
                <button id="btn-period-today" class="btn-secondary btn-sm active"
                    onclick="changePeriod('today')" style="flex: 1; padding: 5px;">Hoje</button>
                <button id="btn-period-week" class="btn-secondary btn-sm" onclick="changePeriod('week')"
                    style="flex: 1; padding: 5px;">Semana</button>
                <button id="btn-period-month" class="btn-secondary btn-sm" onclick="changePeriod('month')"
                    style="flex: 1; padding: 5px;">Mês</button>
            </div>

            <h4
                style="font-size: 0.8rem; color: #666; margin-bottom: 0.5rem; text-align: left; border-top: 1px solid #eee; padding-top: 1rem;">
                Exportar Relatórios</h4>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <button id="btn-export-dashboard" class="btn-secondary" onclick="exportExcel(currentPeriod)"
                    style="width: 100%; font-size: 0.85rem;"><i class="fas fa-file-excel"></i> Planilha de Hoje</button>
                <button id="btn-export-pdf" class="btn-primary" onclick="exportDashboardPDF(currentPeriod)"
                    style="width: 100%; font-size: 0.85rem;"><i class="fas fa-file-pdf"></i> Gerar Relatório PDF</button>
            </div>
        </div>
    </div>
</section>
