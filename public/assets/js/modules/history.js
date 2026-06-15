/**
 * Histórico: Listagem filtrável e pesquisável de acessos
 */

// Cache dos dados carregados — permite busca client-side sem nova requisição
let historyDataCache = [];

/**
 * Carrega o histórico da API respeitando os filtros de período/data.
 * Chamado na inicialização e sempre que o filtro mudar.
 */
async function loadHistory() {
    const period = document.getElementById('history-period-filter')?.value || 'all';
    const date   = document.getElementById('history-date-filter')?.value  || '';

    let url = `${window.API_URL}?action=list_history&period=${period}`;
    if (date) url += `&date=${date}`;

    const tbody = document.getElementById('history-table-body');
    if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:2rem;color:#999;">Carregando...</td></tr>';

    try {
        const response = await fetch(url);
        const history  = await response.json();

        historyDataCache = Array.isArray(history) ? history : [];
        renderHistoryTable(historyDataCache);
    } catch (err) {
        console.error('Erro ao carregar histórico:', err);
        if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:2rem;color:red;">Erro ao carregar dados.</td></tr>';
    }
}

/**
 * Renderiza as linhas da tabela a partir de um array de registros.
 */
function renderHistoryTable(data) {
    const tbody = document.getElementById('history-table-body');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!data || data.length === 0) {
        const period = document.getElementById('history-period-filter')?.value || 'all';
        const date   = document.getElementById('history-date-filter')?.value   || '';
        const queryName = document.getElementById('history-search-name')?.value || '';
        const queryClass = document.getElementById('history-search-class')?.value || '';

        let msg = 'Nenhum registro encontrado.';
        if (queryName && queryClass) msg = `Nenhum resultado para Aluno "${queryName}" e Turma "${queryClass}".`;
        else if (queryName) msg = `Nenhum resultado para Aluno "${queryName}".`;
        else if (queryClass) msg = `Nenhum resultado para Turma "${queryClass}".`;
        else if (date) msg = `Não há registros nessa data (${new Date(date + 'T00:00:00').toLocaleDateString('pt-BR')}).`;
        else if (period === 'today') msg = 'Nenhum acesso registrado hoje.';
        else if (period === 'week')  msg = 'Nenhum acesso registrado esta semana.';
        else if (period === 'month') msg = 'Nenhum acesso registrado este mês.';

        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:2rem;color:#999;">${msg}</td></tr>`;
        return;
    }

    data.forEach(log => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="text-align:center;color:#888;">#${log.id}</td>
            <td style="font-weight:600;">${log.nome}</td>
             <td style="text-align:center;color:#555;">${log.turma || 'N/A'}</td>
             <td style="text-align:center;font-size:0.9rem;">${window.safeFormatLocaleString(log.horario_entrada)}</td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Chamado pelos seletores de período e data.
 * Se período for 'all', limpa o campo de data e recarrega.
 * Qualquer outra mudança dispara nova requisição à API.
 */
function filterHistoryAndExport() {
    const periodSelect = document.getElementById('history-period-filter');
    const dateInput    = document.getElementById('history-date-filter');
    const period       = periodSelect?.value || 'all';
    const date         = dateInput?.value    || '';

    // Quando o usuário digita uma data específica, usa essa data como filtro direto
    if (date && period === 'all' && periodSelect) {
        periodSelect.value = 'today'; // força busca por dia
    }

    // Quando o período volta para "todos", limpa a data
    if (period === 'all' && !date && dateInput) {
        dateInput.value = '';
    }

    loadHistory();
}

/**
 * Busca cliente por nome: filtra o cache sem nova requisição HTTP.
 */
function searchHistory() {
    const queryName = (document.getElementById('history-search-name')?.value || '').toLowerCase().trim();
    const queryClass = (document.getElementById('history-search-class')?.value || '').toLowerCase().trim();

    if (!queryName && !queryClass) {
        renderHistoryTable(historyDataCache);
        return;
    }

    const filtered = historyDataCache.filter(log => {
        const nameMatch = log.nome.toLowerCase().includes(queryName);
        const classMatch = !queryClass || (log.turma && log.turma.toLowerCase().includes(queryClass));
        return nameMatch && classMatch;
    });

    renderHistoryTable(filtered);
}

/**
 * Exporta o histórico atual em XLS respeitando o filtro de período selecionado.
 */
function exportHistoryXLS() {
    const period = document.getElementById('history-period-filter')?.value || 'all';
    const date   = document.getElementById('history-date-filter')?.value   || '';

    let url = `${window.API_URL}?action=export_excel&period=${period}`;
    if (date) url += `&date=${date}`;

    showToast('Gerando XLS do histórico...', 'success');
    window.location.href = url;
}


