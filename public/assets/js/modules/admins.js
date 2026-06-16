/**
 * Admins: Gestão de Administradores e Professores do Sistema
 *
 * Módulo responsável por listar, cadastrar, resetar senha e excluir
 * usuários do tipo 'admin' e 'professor' da tabela admins.
 */

let _currentAdminTab = 'admin'; // Tab ativa: 'admin' ou 'professor'

/**
 * Troca a aba ativa entre Administradores e Professores.
 */
function switchAdminTab(tipo) {
    _currentAdminTab = tipo;

    // Atualiza visual das abas
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
        const isActive = btn.dataset.tipo === tipo;
        btn.style.color           = isActive ? 'var(--primary, #BC0000)' : '#999';
        btn.style.borderBottom    = isActive ? '3px solid var(--primary, #BC0000)' : '3px solid transparent';
        btn.style.fontWeight      = isActive ? '800' : '600';
    });

    // Mostra/esconde painéis
    document.querySelectorAll('.admin-tab-panel').forEach(panel => {
        panel.style.display = panel.dataset.tipo === tipo ? 'block' : 'none';
    });

    // Carrega os dados da aba selecionada
    loadAdmins(tipo);
}

/**
 * Carrega e renderiza a lista de admins ou professores na tabela.
 */
async function loadAdmins(tipo) {
    tipo = tipo || _currentAdminTab;

    const tbody = document.getElementById(`admins-table-body-${tipo}`);
    if (!tbody) return;

    // Reset da seleção em lote ao recarregar a lista
    const masterCb = document.getElementById(`select-all-${tipo}`);
    if (masterCb) masterCb.checked = false;
    const btnDelete = document.getElementById(`btn-delete-selected-${tipo}`);
    if (btnDelete) btnDelete.style.display = 'none';

    const colspanVal = window.CURRENT_USER_TIPO === 'admin' ? 5 : 4;

    tbody.innerHTML = `
        <tr>
            <td colspan="${colspanVal}" style="text-align:center; padding:2.5rem; color:#aaa;">
                <i class="fas fa-spinner fa-spin"></i> Carregando...
            </td>
        </tr>`;

    try {
        const response = await fetch(`${window.API_URL}?action=list_admins&tipo=${tipo}`);
        const data     = await response.json();

        if (!Array.isArray(data)) {
            tbody.innerHTML = `<tr><td colspan="${colspanVal}" style="text-align:center; color:#cc0000; padding:2rem;">Erro ao carregar dados.</td></tr>`;
            return;
        }

        if (data.length === 0) {
            const label = tipo === 'admin' ? 'administradores' : 'professores';
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colspanVal}" style="text-align:center; padding:2.5rem; color:#bbb;">
                        <i class="fas fa-user-slash" style="font-size:1.5rem; display:block; margin-bottom:0.5rem;"></i>
                        Nenhum ${label} cadastrado ainda.
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = '';
        data.forEach(admin => {
            const criado = window.safeParseDate
                ? window.safeParseDate(admin.criado_at)?.toLocaleDateString('pt-BR') ?? admin.criado_at
                : admin.criado_at;

            const tr = document.createElement('tr');

            let checkboxCol = '';
            if (window.CURRENT_USER_TIPO === 'admin') {
                checkboxCol = `
                    <td style="padding: 12px 16px; text-align: center;">
                        <input type="checkbox" class="${tipo}-select" value="${admin.id}" onclick="updateSelectedAdminsState('${tipo}')">
                    </td>
                `;
            }

            tr.innerHTML = `
                ${checkboxCol}
                <td style="padding: 12px 16px; color: #888; font-size: 0.85rem;">${admin.id}</td>
                <td style="padding: 12px 16px;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(188,0,0,0.1); border: 1px solid rgba(188,0,0,0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas ${tipo === 'admin' ? 'fa-user-shield' : 'fa-chalkboard-teacher'}" style="font-size: 0.8rem; color: var(--primary, #BC0000);"></i>
                        </div>
                        <strong style="color: var(--text, #1a1a2e);">${admin.usuario}</strong>
                    </div>
                </td>
                <td style="padding: 12px 16px; color: #888; font-size: 0.88rem;">${criado}</td>
                <td style="padding: 12px 16px;">
                    <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                        ${(function() {
                            let buttons = '';
                            if (window.CURRENT_USER_TIPO === 'professor') {
                                if (admin.tipo === 'professor') {
                                    buttons += `
                                        <button onclick="resetAdminPassword(${admin.id}, '${admin.usuario}')"
                                            style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; cursor: pointer;
                                                   border: 1px solid #e0a800; background: rgba(255,193,7,0.08); color: #b38600;
                                                   font-weight: 600; display: flex; align-items: center; gap: 5px;
                                                   transition: all 0.2s ease;"
                                            onmouseover="this.style.background='rgba(255,193,7,0.18)'"
                                            onmouseout="this.style.background='rgba(255,193,7,0.08)'">
                                            <i class="fas fa-key"></i> Resetar Senha
                                        </button>
                                    `;
                                } else {
                                    buttons += `<span style="color: #999; font-size: 0.8rem; font-style: italic;">Sem permissões</span>`;
                                }
                            } else {
                                // Botão editar nome — apenas para professores
                                if (tipo === 'professor') {
                                    const safeName = admin.usuario.replace(/"/g, '&quot;');
                                    buttons += `
                                        <button
                                            data-edit-id="${admin.id}"
                                            data-edit-nome="${safeName}"
                                            onclick="openEditAdminNameModal(this)"
                                            style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; cursor: pointer;
                                                   border: 1px solid #0d6efd; background: rgba(13,110,253,0.06); color: #0d6efd;
                                                   font-weight: 600; display: flex; align-items: center; gap: 5px;
                                                   transition: all 0.2s ease;"
                                            onmouseover="this.style.background='rgba(13,110,253,0.15)'"
                                            onmouseout="this.style.background='rgba(13,110,253,0.06)'">
                                            <i class="fas fa-edit"></i> Editar Nome
                                        </button>
                                    `;
                                }
                                buttons += `
                                    <button onclick="resetAdminPassword(${admin.id}, '${admin.usuario}')"
                                        style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; cursor: pointer;
                                               border: 1px solid #e0a800; background: rgba(255,193,7,0.08); color: #b38600;
                                               font-weight: 600; display: flex; align-items: center; gap: 5px;
                                               transition: all 0.2s ease;"
                                        onmouseover="this.style.background='rgba(255,193,7,0.18)'"
                                        onmouseout="this.style.background='rgba(255,193,7,0.08)'">
                                        <i class="fas fa-key"></i> Resetar Senha
                                    </button>
                                    <button onclick="deleteAdmin(${admin.id}, '${admin.usuario}')"
                                        style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; cursor: pointer;
                                               border: 1px solid rgba(220,53,69,0.3); background: rgba(220,53,69,0.06); color: #dc3545;
                                               font-weight: 600; display: flex; align-items: center; gap: 5px;
                                               transition: all 0.2s ease;"
                                        onmouseover="this.style.background='rgba(220,53,69,0.15)'"
                                        onmouseout="this.style.background='rgba(220,53,69,0.06)'">
                                        <i class="fas fa-trash-alt"></i> Excluir
                                    </button>
                                `;
                            }
                            return buttons;
                        })()}
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

    } catch (err) {
        console.error('Erro ao carregar usuários:', err);
        tbody.innerHTML = `<tr><td colspan="${colspanVal}" style="text-align:center; color:#cc0000; padding:2rem;">Falha na conexão. Tente novamente.</td></tr>`;
    }
}

/**
 * Cadastra um novo administrador ou professor.
 */
async function createAdmin(tipo) {
    tipo = tipo || _currentAdminTab;

    const usuarioInput = document.getElementById(`admin-usuario-${tipo}`);
    const senhaInput   = document.getElementById(`admin-senha-${tipo}`);

    const usuario = usuarioInput?.value?.trim() || '';
    const senha   = senhaInput?.value?.trim()   || '';

    if (!usuario) return showToast('Informe o nome de usuário.', 'error');
    if (!senha)   return showToast('Informe a senha.', 'error');
    if (senha.length < 4) return showToast('A senha deve ter no mínimo 4 caracteres.', 'error');

    // Desabilitar botão para evitar double-click
    const btnId  = `admin-btn-create-${tipo}`;
    const btn    = document.getElementById(btnId);
    const orig   = btn?.innerHTML;
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cadastrando...'; }

    try {
        const res  = await fetch(`${window.API_URL}?action=create_admin`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ usuario, senha, tipo })
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            usuarioInput.value = '';
            senhaInput.value   = '';
            loadAdmins(tipo);
        } else {
            showToast(data.message || 'Erro ao cadastrar.', 'error');
        }
    } catch (err) {
        console.error('Erro ao cadastrar:', err);
        showToast('Erro de conexão ao cadastrar.', 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = orig; }
    }
}

/**
 * Reseta a senha do usuário para 'senaisp' após confirmação.
 */
async function resetAdminPassword(id, usuario) {
    const confirmed = await window.openConfirmModal(
        'Resetar Senha',
        `Deseja resetar a senha de "${usuario}"?\nA nova senha será: senaisp`,
        'Sim, Resetar'
    );
    if (!confirmed) return;

    try {
        const res  = await fetch(`${window.API_URL}?action=reset_admin_password&id=${id}&tipo=${_currentAdminTab}`);
        const data = await res.json();
        showToast(data.message, data.success ? 'success' : 'error');
    } catch (err) {
        showToast('Erro de conexão ao resetar senha.', 'error');
    }
}

/**
 * Exclui um admin ou professor permanentemente após confirmação.
 */
async function deleteAdmin(id, usuario) {
    const confirmed = await window.openConfirmModal(
        'Excluir Usuário',
        `Deseja excluir permanentemente o usuário "${usuario}"?\nEsta ação não pode ser desfeita.`,
        'Sim, Excluir'
    );
    if (!confirmed) return;

    try {
        const res  = await fetch(`${window.API_URL}?action=delete_admin&id=${id}&tipo=${_currentAdminTab}`);
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            loadAdmins(_currentAdminTab);
        } else {
            showToast(data.message || 'Erro ao excluir.', 'error');
        }
    } catch (err) {
        showToast('Erro de conexão ao excluir.', 'error');
    }
}

// Inicialização automática ao entrar na página de admins
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('sec-admins')) {
        switchAdminTab('admin');
    }
});

window.triggerTeacherExcelImport = function () {
    document.getElementById('teacher-excel-import-file').click();
}

window.handleTeacherExcelImport = async function (event) {
    const file = event.target.files[0];
    if (!file) return;

    event.target.value = '';

    if (!(/(\.xlsx|\.xls|\.csv)$/i.test(file.name))) {
        showToast("Arquivo inválido. Envie apenas planilhas nos formatos .xlsx, .xls ou .csv", "error");
        return;
    }

    if (file.size === 0) {
        showToast("O arquivo selecionado está vazio.", "error");
        return;
    }

    const formData = new FormData();
    formData.append('excel_file', file);

    showToast("Importando professores via planilha...", "info");

    try {
        const res = await fetch('api.php?action=import_teachers_excel', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            showToast(`${data.imported} professores importados com sucesso! Duplicados: ${data.duplicates}, Erros: ${data.errors}`, "success");
            loadAdmins('professor');
        } else {
            showToast(data.message || "Erro ao importar professores.", "error");
        }
    } catch (err) {
        console.error("Erro ao importar:", err);
        showToast("Erro de conexão ao tentar importar.", "error");
    }
}

/**
 * Alterna a seleção de todos os usuários (admin ou professor) na tabela com base no checkbox mestre.
 */
window.toggleAllAdmins = function (masterCb, tipo) {
    const checkboxes = document.querySelectorAll(`.${tipo}-select`);
    checkboxes.forEach(cb => {
        cb.checked = masterCb.checked;
    });
    window.updateSelectedAdminsState(tipo);
};

/**
 * Atualiza a visibilidade do botão de exclusão em lote com base nas seleções.
 */
window.updateSelectedAdminsState = function (tipo) {
    const selected = document.querySelectorAll(`.${tipo}-select:checked`);
    const btn = document.getElementById(`btn-delete-selected-${tipo}`);
    if (!btn) return;

    if (selected.length > 0) {
        btn.style.display = 'inline-flex';
        btn.innerHTML = `<i class="fas fa-trash-alt"></i> Excluir Selecionados (${selected.length})`;
    } else {
        btn.style.display = 'none';
    }
};

/**
 * Executa a exclusão em lote dos usuários (admin ou professor) selecionados.
 */
window.deleteSelectedAdmins = async function (tipo) {
    const selected = document.querySelectorAll(`.${tipo}-select:checked`);
    if (selected.length === 0) return;

    const ids = Array.from(selected).map(cb => cb.value);
    const count = ids.length;

    const label = tipo === 'admin' ? 'administradores' : 'professores';
    const title = tipo === 'admin' ? 'Excluir Admins em Lote' : 'Excluir Professores em Lote';
    const msg = `Deseja excluir permanentemente os ${count} ${label} selecionado(s)?\nEsta ação não pode ser desfeita.`;

    const confirmed = await window.openConfirmModal(title, msg, "Sim, Excluir");
    if (!confirmed) return;

    // Mostra um toast informativo do início do processo
    showToast(`Excluindo ${count} ${label} em lote...`, 'info');

    let successes = 0;
    let failures = 0;

    try {
        // Executa as exclusões sequencialmente para evitar conflitos de sessão/concorrência
        for (const id of ids) {
            try {
                const res = await fetch(`${window.API_URL}?action=delete_admin&id=${id}&tipo=${tipo}`)
                    .then(r => r.json());
                if (res.success) {
                    successes++;
                } else {
                    failures++;
                }
            } catch (err) {
                failures++;
            }
        }

        if (successes > 0) {
            showToast(`${successes} ${label} excluído(s) com sucesso.`, 'success');
        }
        if (failures > 0) {
            showToast(`${failures} ${label} falharam ao excluir.`, 'error');
        }

        loadAdmins(tipo); // Recarrega a tabela correspondente
    } catch (err) {
        showToast("Erro de comunicação ao processar exclusão em lote.", 'error');
    }
};

/**
 * Abre o modal customizado para editar o nome de um professor.
 */
window.openEditAdminNameModal = function (btn) {
    const id   = btn.getAttribute('data-edit-id');
    const nome = btn.getAttribute('data-edit-nome');

    document.getElementById('edit-admin-name-id').value    = id;
    document.getElementById('edit-admin-name-input').value = nome;
    document.getElementById('edit-admin-name-label').textContent = `Nome atual: ${nome}`;

    document.getElementById('modal-edit-admin-name').classList.add('active');
    setTimeout(() => document.getElementById('edit-admin-name-input').select(), 100);
};

window.closeEditAdminNameModal = function () {
    document.getElementById('modal-edit-admin-name').classList.remove('active');
};

window.saveAdminName = async function () {
    const id       = document.getElementById('edit-admin-name-id').value;
    const novoNome = document.getElementById('edit-admin-name-input').value.trim();

    if (!novoNome) {
        showToast('O nome não pode estar vazio.', 'error');
        return;
    }

    const btn = document.getElementById('edit-admin-name-save-btn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    try {
        const res  = await fetch(`${window.API_URL}?action=update_admin_name`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ id: parseInt(id), novo_nome: novoNome })
        });
        const data = await res.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            window.closeEditAdminNameModal();
            loadAdmins('professor');
        }
    } catch (err) {
        showToast('Erro de conexão ao atualizar nome.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
};
