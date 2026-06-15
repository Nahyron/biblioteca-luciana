/**
 * Dashboard: Gráficos e Estatísticas
 */

// Estado global do filtro de tipo de aluno (Biometria)
window.currentAccessType = window.currentAccessType || 'all';

/**
 * Inicializa o dashboard puxando estatísticas gerais da API.
 * Atualiza os contadores de acessos, total de alunos e os status da visão.
 */
async function initDashboard() {
    try {
        const response = await fetch(`${window.API_URL}?action=dashboard_stats&period=today&type=${window.currentAccessType}`);
        const data = await response.json();

        const countEl = document.getElementById('total-acessos');
        if (countEl) countEl.innerText = data.total_acessos || 0;

        const usersEl = document.getElementById('total-alunos');
        if (usersEl) usersEl.innerText = data.total_users || 0;

        initCharts('today');
    } catch (err) {
        console.error("Erro no dashboard:", err);
    }
}

async function initCharts(period = null) {
    const activePeriod = period || currentPeriod;
    const canvas = document.getElementById('flowChart');
    if (!canvas) return;

    // Control date-picker visibility and default value
    const datePicker = document.getElementById('dashboard-date-picker');
    if (datePicker) {
        if (activePeriod === 'today') {
            datePicker.style.display = 'block';
            if (!datePicker.value) {
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                datePicker.value = `${yyyy}-${mm}-${dd}`;
            }
        } else {
            datePicker.style.display = 'none';
        }
    }

    if (flowChartInstance) flowChartInstance.destroy();

    try {
        let url = `api.php?action=dashboard_stats&period=${activePeriod}&type=${window.currentAccessType}`;
        if (activePeriod === 'today' && datePicker && datePicker.value) {
            url += `&date=${datePicker.value}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        const totalAcessos = document.getElementById('total-acessos');
        if (totalAcessos) {
            totalAcessos.innerText = data.total_acessos || 0;
        }

        const rawData = data.flow_data || [];
        let chartLabels = rawData.map(item => item.label);
        let chartValues = rawData.map(item => item.count);

        if (chartLabels.length === 0) {
            chartLabels = ['Sem registros'];
            chartValues = [0];
        }

        const chartConfig = {
            type: currentChartType,
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Fluxo de Pessoas',
                    data: chartValues,
                    borderColor: '#D2232A',
                    backgroundColor: currentChartType === 'line' ? 'rgba(210, 35, 42, 0.1)' : '#D2232A',
                    fill: currentChartType === 'line',
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#D2232A',
                    pointBorderWidth: 2,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        };

        flowChartInstance = new Chart(canvas.getContext('2d'), chartConfig);

        const titles = { 'today': 'Hoje', 'week': 'Semana', 'month': 'Mês' };
        const periodTitle = document.getElementById('stat-period-title');
        if (periodTitle) {
            if (activePeriod === 'today' && datePicker && datePicker.value) {
                // Format YYYY-MM-DD to DD/MM/YYYY
                const parts = datePicker.value.split('-');
                const formattedDate = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : datePicker.value;
                periodTitle.innerText = `Acessos em ${formattedDate}`;
            } else {
                periodTitle.innerText = `Acessos ${titles[activePeriod]}`;
            }
        }

    } catch (err) {
        console.error("Erro ao carregar dados do dashboard:", err);
    }
}

// Removida renderDailyChart redundante

function changeChartType(type) {
    currentChartType = type;
    document.querySelectorAll('.chart-controls button').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById(`btn-type-${type}`);
    if (activeBtn) activeBtn.classList.add('active');
    initCharts();
}

function changePeriod(period) {
    currentPeriod = period;

    document.querySelectorAll('.period-selector button').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById(`btn-period-${period}`);
    if (activeBtn) activeBtn.classList.add('active');

    const excelBtn = document.getElementById('btn-export-dashboard');
    const pdfBtn = document.getElementById('btn-export-pdf');
    
    if (excelBtn || pdfBtn) {
        const labels = { 
            'today': 'de Hoje', 
            'week': 'da Semana', 
            'month': 'do Mês' 
        };
        
        if (excelBtn) {
            excelBtn.innerHTML = `<i class="fas fa-file-excel"></i> Planilha ${labels[period]}`;
            excelBtn.setAttribute('onclick', `exportExcel('${period}')`);
        }
        
        if (pdfBtn) {
            pdfBtn.innerHTML = `<i class="fas fa-file-pdf"></i> PDF ${labels[period]} (Com Gráficos)`;
            pdfBtn.setAttribute('onclick', `exportDashboardPDF('${period}')`);
        }
    }

    initCharts(period);
}

function changeDashboardDate(dateVal) {
    initCharts('today');
}

function changeAccessType(type) {
    window.currentAccessType = type;

    // Controla classes ativas dos seletores
    document.querySelectorAll('.type-selector button').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById(`btn-type-${type}`);
    if (activeBtn) activeBtn.classList.add('active');

    // Recarrega o gráfico e contador com o novo filtro ativo
    initCharts();
}

