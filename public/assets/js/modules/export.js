/**
 * Exportação: Excel, PDF e CSV
 */

function exportExcel(period = 'today', date = null) {
    let url = `api.php?action=export_excel&period=${period}`;
    if (date) url += `&date=${date}`;
    window.location.href = url;
}

/**
 * Exportação em XLS estilizado dos alunos.
 */
window.exportStudentsXLS = function() {
    window.location.href = window.API_URL + '?action=export_students_xls';
}

/**
 * Abre o modal de opções avançadas de exportação.
 */
window.openExportModal = function() {
    const modal = document.getElementById('modal-export-advanced');
    if (modal) modal.classList.add('active');
    else console.error("Modal de exportação não encontrado na página.");
}

/**
 * Fecha o modal de exportação avançada.
 */
window.closeExportModal = function() {
    const modal = document.getElementById('modal-export-advanced');
    if (modal) modal.classList.remove('active');
}

/**
 * Coleta os dados do formulário do modal e chama a exportação Excel.
 */
window.runAdvancedExport = function() {
    const periodOpt = document.querySelector('input[name="export-period"]:checked');
    const period = periodOpt ? periodOpt.value : 'today';
    const date = document.getElementById('export-date').value;

    if (!date) {
        showToast("Selecione uma data para o filtro.", "error");
        return;
    }

    exportExcel(period, date);
    showToast("Gerando sua planilha...", "success");
    closeExportModal();
}

/**
 * GERA RELATÓRIO PDF DINÂMICO COM GRÁFICOS (Barra + Linha)
 * Captura as estatísticas atuais e gera um documento profissional via html2pdf.js.
 * @param {string} period 'today', 'week', 'month'
 */
async function exportDashboardPDF(period = 'today') {
    const labels = { 'today': 'Hoje', 'week': 'da Semana', 'month': 'do Mês' };
    const periodLabel = labels[period];
    const totalAcessos = document.getElementById('total-acessos')?.innerText || '0';
    
    showToast("Preparando relatório PDF... Aguarde.", "success");

    // Wrapper que esconde o relatório de forma segura sem quebrar o HTML2Canvas e evita GLITCH de scroll
    const wrapper = document.createElement('div');
    wrapper.style.position = 'fixed';
    wrapper.style.top = '0';
    wrapper.style.left = '0';
    wrapper.style.width = '1px';
    wrapper.style.height = '1px';
    wrapper.style.overflow = 'hidden';
    wrapper.style.opacity = '0';
    wrapper.style.pointerEvents = 'none';

    // Container do Relatório (Renderizado dentro do wrapper invísivel)
    const element = document.createElement('div');
    element.className = 'container-relatorio-pdf';
    element.style.boxSizing = 'border-box';
    element.style.padding = '25px'; 
    element.style.color = '#333';
    element.style.backgroundColor = '#ffffff';
    element.style.fontFamily = "'Inter', sans-serif";
    element.style.width = '710px'; // Largura Máxima ideal (Pixel Perfecto para A4 Margin 10mm)

    // Estrutura Visual do Relatório (Branding SENAI) - Fontes Ajustadas Proporcionalmente
    element.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #D2232A; padding-bottom: 12px; margin-bottom: 25px;">
            <div>
                <h1 style="color: #D2232A; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -1px;">SISTEMA BIBLIOTECA</h1>
                <p style="margin: 4px 0 0 0; color: #666; font-size: 12px;">Controle de Acesso por Visão Computacional</p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-weight: bold; font-size: 14px;">RELATÓRIO DE FLUXO</p>
                <p style="margin: 3px 0 0 0; color: #888; font-size: 11px;">Emissão: ${new Date().toLocaleString('pt-BR')}</p>
            </div>
        </div>
        
        <div style="display: flex; gap: 15px; margin-bottom: 25px; background: #f8f9fa; padding: 18px; border-radius: 10px; border: 1px solid #eee; box-sizing: border-box;">
            <div style="flex: 1;">
                <p style="margin: 0; color: #666; font-size: 11px; text-transform: uppercase; font-weight: bold;">Período Analisado</p>
                <p style="margin: 5px 0 0 0; font-size: 17px; font-weight: bold; color: #333;">${periodLabel}</p>
            </div>
            <div style="flex: 1; border-left: 2px solid #ddd; padding-left: 15px;">
                <p style="margin: 0; color: #666; font-size: 11px; text-transform: uppercase; font-weight: bold;">Total de Acessos Registrados</p>
                <p style="margin: 5px 0 0 0; font-size: 17px; font-weight: bold; color: #D2232A;">${totalAcessos}</p>
            </div>
        </div>

        <div style="margin-bottom: 25px;">
            <h3 style="color: #333; font-size: 15px; border-left: 4px solid #D2232A; padding-left: 10px; margin-bottom: 15px;">Tendência de Fluxo Horário (Linha)</h3>
            <div id="pdf-chart-line" style="background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 12px; display: flex; justify-content: center; box-sizing: border-box;"></div>
        </div>

        <div style="margin-bottom: 20px;">
            <h3 style="color: #333; font-size: 15px; border-left: 4px solid #D2232A; padding-left: 10px; margin-bottom: 15px;">Frequência por Período (Barra)</h3>
            <div id="pdf-chart-bar" style="background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 12px; display: flex; justify-content: center; box-sizing: border-box;"></div>
        </div>
        
        <div style="margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 12px; font-size: 10px; color: #999;">
            Este é um documento eletrônico gerado automaticamente. Todos os dados são extraídos em tempo real do banco de dados institucional.
        </div>
    `;

    wrapper.appendChild(element);
    document.body.appendChild(wrapper);

    try {
        const datePicker = document.getElementById('dashboard-date-picker');
        let url = `api.php?action=dashboard_stats&period=${period}`;
        if (period === 'today' && datePicker && datePicker.value) {
            url += `&date=${datePicker.value}`;
        }
        const response = await fetch(url);
        const data = await response.json();
        const rawData = data.flow_data || [];
        let chartLabels = rawData.map(item => item.label);
        let flowData = rawData.map(item => item.count);

        if (chartLabels.length === 0) {
            chartLabels = ['Sem registros'];
            flowData = [0];
        }

        const renderChartForPDF = (id, type) => {
            const container = element.querySelector(`#${id}`);
            const canvas = document.createElement('canvas');
            // Dimensões Máximas Calculadas (Largura 710 - Padding Content)
            canvas.width = 630;
            canvas.height = 220;
            container.appendChild(canvas);
            
            return new Chart(canvas.getContext('2d'), {
                type: type,
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Volume de Acessos',
                        data: flowData,
                        borderColor: '#D2232A',
                        backgroundColor: type === 'line' ? 'rgba(210, 35, 42, 0.1)' : '#D2232A',
                        fill: type === 'line',
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#D2232A',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: true,
                    animation: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        };

        renderChartForPDF('pdf-chart-line', 'line');
        renderChartForPDF('pdf-chart-bar', 'bar');

        // AGUARDA ESTABILIZAÇÃO (1 segundo para garantir que o Canvas termine)
        await new Promise(resolve => setTimeout(resolve, 1000));

        const opt = {
            margin: 10,
            filename: `Relatorio_Biblioteca_${period}_${new Date().toISOString().split('T')[0]}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 2, 
                useCORS: true, 
                logging: false,
                scrollY: 0,
                scrollX: 0
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        await html2pdf().set(opt).from(element).save();
        showToast("Relatório PDF exportado com sucesso!", "success");

    } catch (err) {
        console.error("Erro na geração do PDF:", err);
        showToast("Falha ao gerar o PDF. Verifique os dados.", "error");
    } finally {
        document.body.removeChild(wrapper);
    }
}
