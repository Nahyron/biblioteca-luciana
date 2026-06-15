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

    tbody.innerHTML = `
        <tr>
            <td colspan="4" style="text-align:center; padding:2.5rem; color:#aaa;">
                <i class="fas fa-spinner fa-spin"></i> Carregando...
            </td>
        </tr>`;

    try {
        const response = await fetch(`${window.API_URL}?action=list_admins&tipo=${tipo}`);
        const data     = await response.json();

        if (!Array.isArray(data)) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#cc0000; padding:2rem;">Erro ao carregar dados.</td></tr>`;
            return;
        }

        if (data.length === 0) {
            const label = tipo === 'admin' ? 'administradores' : 'professores';
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align:center; padding:2.5rem; color:#bbb;">
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
            tr.innerHTML = `
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
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#cc0000; padding:2rem;">Falha na conexão. Tente novamente.</td></tr>`;
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
        const res  = await fetch(`${window.API_URL}?action=reset_admin_password&id=${id}`);
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
        const res  = await fetch(`${window.API_URL}?action=delete_admin&id=${id}`);
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
