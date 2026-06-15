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
    const startDate = document.getElementById('history-start-date')?.value || '';
    const endDate   = document.getElementById('history-end-date')?.value   || '';

    let url = `${window.API_URL}?action=list_history&period=${period}`;
    if (startDate && endDate) {
        url += `&start_date=${startDate}&end_date=${endDate}`;
    }

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
        const startDate = document.getElementById('history-start-date')?.value || '';
        const endDate   = document.getElementById('history-end-date')?.value   || '';
        const queryName = document.getElementById('history-search-name')?.value || '';
        const queryClass = document.getElementById('history-search-class')?.value || '';

        let msg = 'Nenhum registro encontrado.';
        if (queryName && queryClass) msg = `Nenhum resultado para Aluno "${queryName}" e Turma "${queryClass}".`;
        else if (queryName) msg = `Nenhum resultado para Aluno "${queryName}".`;
        else if (queryClass) msg = `Nenhum resultado para Turma "${queryClass}".`;
        else if (startDate && endDate) msg = `Não há registros no período de ${new Date(startDate + 'T00:00:00').toLocaleDateString('pt-BR')} até ${new Date(endDate + 'T00:00:00').toLocaleDateString('pt-BR')}.`;
        else if (period === 'today') msg = 'Nenhum acesso registrado hoje.';
        else if (period === 'week')  msg = 'Nenhum acesso registrado esta semana.';
        else if (period === 'month') msg = 'Nenhum acesso registrado este mês.';

        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:2rem;color:#999;">${msg}</td></tr>`;
        return;
    }

    data.forEach(log => {
        const tr = document.createElement('tr');
        
        let nomeHtml = `<strong style="color: var(--text, #1a1a2e);">${log.nome}</strong>`;
        let timestampHtml = window.safeFormatLocaleString(log.horario_entrada);
        
        if (log.acao === 'desativacao') {
            nomeHtml = `<span style="color: #dc3545; font-weight: 700;"><i class="fas fa-user-slash" style="margin-right: 5px;"></i>${log.nome} (Desativado)</span>`;
            timestampHtml = `${window.safeFormatLocaleString(log.horario_entrada)} <br> <span style="font-size: 0.78rem; color: #777; font-weight: 600; background: #ffebeb; padding: 2px 6px; border-radius: 4px; border: 1px solid #ffcccc; margin-top: 4px; display: inline-block;"><i class="fas fa-user-shield"></i> Por: ${log.operador || 'Sistema'}</span>`;
        }

        tr.innerHTML = `
            <td style="text-align:center;color:#888;">#${log.id}</td>
            <td>${nomeHtml}</td>
            <td style="text-align:center;color:#555;">${log.turma || 'N/A'}</td>
            <td style="text-align:center;font-size:0.9rem;">${timestampHtml}</td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Chamado pelos seletores de período e data.
 */
function filterHistoryAndExport() {
    const periodSelect   = document.getElementById('history-period-filter');
    const startDateInput = document.getElementById('history-start-date');
    const endDateInput   = document.getElementById('history-end-date');
    const period         = periodSelect?.value || 'all';
    const startDate      = startDateInput?.value    || '';
    const endDate        = endDateInput?.value      || '';

    // Se preencheu o período de data personalizado, reseta o select de período pré-definido para 'todos'
    if ((startDate || endDate) && period !== 'all' && periodSelect) {
        periodSelect.value = 'all';
    }

    // Se limpou as datas de período personalizado, permite nova busca
    loadHistory();
}

/**
 * Busca cliente por nome e/ou turma: filtra o cache sem nova requisição HTTP.
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
 * Exporta o histórico atual em XLS respeitando os filtros selecionados.
 */
function exportHistoryXLS() {
    const period = document.getElementById('history-period-filter')?.value || 'all';
    const startDate = document.getElementById('history-start-date')?.value || '';
    const endDate   = document.getElementById('history-end-date')?.value   || '';

    let url = `${window.API_URL}?action=export_excel&period=${period}`;
    if (startDate && endDate) {
        url += `&start_date=${startDate}&end_date=${endDate}`;
    }

    showToast('Gerando XLS do histórico...', 'success');
    window.location.href = url;
}

/**
 * Carrega a lista de turmas dinamicamente para o seletor de busca do histórico.
 */
async function loadClassesFilter() {
    const select = document.getElementById('history-search-class');
    if (!select) return;

    try {
        const response = await fetch(`${window.API_URL}?action=list_classes`);
        const classes  = await response.json();
        
        if (Array.isArray(classes)) {
            classes.forEach(c => {
                // Pula turmas de controle
                if (['Sem Turma', 'N/A', 'N/A '].includes(c.nome)) return;
                const opt = document.createElement('option');
                opt.value = c.nome;
                opt.textContent = c.nome;
                select.appendChild(opt);
            });
        }
    } catch (err) {
        console.error('Erro ao carregar turmas para filtro do histórico:', err);
    }
}


