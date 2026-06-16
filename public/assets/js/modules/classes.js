/**
 * Turmas: Gestão de turmas e atribuição de alunos
 */

let currentManageClass = null;
let classesCache = []; // Cache de turmas ativas
let inactiveClassesCache = []; // Cache de turmas inativas
let _importedFilesHistory = {}; // Controle temporário em memória para uploads ativos
try {
    localStorage.removeItem('biblioteca_vision_imported_files'); // Limpa resquícios do histórico persistente anterior
} catch (e) { }

function canManageCurrentClass() {
    if (!currentManageClass) return false;
    const isProfessor = window.CURRENT_USER_TIPO === 'professor';
    if (!isProfessor) return true;

    const clsObj = classesCache.find(c => c.id === currentManageClass.id) || inactiveClassesCache.find(c => c.id === currentManageClass.id);
    if (!clsObj) return false;

    return clsObj.can_manage === true;
}

async function loadClasses() {
    try {
        const [activeRes, inactiveRes] = await Promise.all([
            fetch('api.php?action=list_classes'),
            fetch('api.php?action=list_inactive_classes')
        ]);
        const classes         = await activeRes.json();
        const inactiveClasses = await inactiveRes.json();

        classesCache         = Array.isArray(classes)         ? classes         : [];
        inactiveClassesCache = Array.isArray(inactiveClasses) ? inactiveClasses : [];

        renderClassesGrid(classesCache);
        renderInactiveClassesSection(inactiveClassesCache);
        updateClassSelects(classesCache);
    } catch (err) {
        console.error("Erro ao carregar turmas:", err);
        showToast("Falha ao carregar as turmas", "error");
    }
}

/**
 * Renderiza os cards de turma ativa a partir de um array (do cache ou filtrado).
 */
function renderClassesGrid(classes) {
    const grid = document.getElementById('classes-grid');
    if (!grid) return;
    grid.innerHTML = '';

    if (!classes || classes.length === 0) {
        grid.innerHTML = '<div style="padding:2rem;text-align:center;color:#666;grid-column:1/-1;">Nenhuma turma encontrada.</div>';
        return;
    }

    classes.forEach(cls => {
        // Pula turmas virtuais de controle
        if (['Sem Turma', 'N/A', 'N/A '].includes(cls.nome)) return;

        const canManage = cls.can_manage === true;

        const card = document.createElement('div');
        card.className = 'card';
        card.style.cursor = 'pointer';
        card.style.transition = 'transform 0.2s, box-shadow 0.2s';

        card.onmouseover = () => { card.style.transform = 'translateY(-3px)'; card.style.boxShadow = '0 6px 12px rgba(0,0,0,0.1)'; };
        card.onmouseout  = () => { card.style.transform = ''; card.style.boxShadow = ''; };

        let actionButtons = '';
        if (canManage) {
            let permBtn = '';
            if (window.CURRENT_USER_TIPO === 'admin') {
                permBtn = `<button class="btn-action-edit" style="padding: 5px; font-size: 0.8rem; background: rgba(247,127,0,0.08); border-color: rgba(247,127,0,0.3); color: #f77f00; margin-right: 3px;" title="Professores Autorizados" onclick="openClassPermissionsModal(${cls.id}, '${cls.nome}', event)"><i class="fas fa-user-lock"></i></button>`;
            }
            actionButtons = `
                ${permBtn}
                <button class="btn-action-edit" style="padding: 5px; font-size: 0.8rem; margin-right: 3px;" title="Editar" onclick="editClass(${cls.id}, '${cls.nome}', event)"><i class="fas fa-edit"></i></button>
                <button class="btn-action-delete" style="padding: 5px; font-size: 0.8rem;" title="Desativar Turma" onclick="deactivateClassDirect(${cls.id}, '${cls.nome}', event)"><i class="fas fa-ban"></i></button>
            `;
        }

        const manageBtnText = canManage ? 'Gerenciar Alunos' : 'Visualizar Alunos';

        card.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h4 style="margin: 0; font-size: 1.25rem; color: var(--text);">${cls.nome}</h4>
                        <span style="font-size: 0.85rem; color: #666;">Criada em ${new Date(cls.created_at).toLocaleDateString('pt-BR')}</span>
                    </div>
                    <div>
                        ${actionButtons}
                    </div>
                </div>
                <button class="btn-secondary" style="width: 100%; margin-top: 1rem;" onclick="showClassStudentsView('${cls.nome}', ${cls.id}, event)">
                    ${manageBtnText}
                </button>
            `;
        grid.appendChild(card);
    });
}

let isInactiveSectionVisible = false; // Estado global para visibilidade da seção

/**
 * Renderiza apenas os cards de turmas inativas no grid.
 */
function renderInactiveClassesGrid(classes) {
    const grid = document.getElementById('inactive-classes-grid');
    if (!grid) return;
    grid.innerHTML = '';

    const filtered = (classes || []).filter(c => !['Sem Turma', 'N/A', 'N/A '].includes(c.nome));

    if (filtered.length === 0) {
        grid.innerHTML = '<div style="padding:2rem;text-align:center;color:#888;grid-column:1/-1;">Nenhuma turma desativada encontrada.</div>';
        return;
    }

    filtered.forEach(cls => {
        const canManage = cls.can_manage === true;

        const card = document.createElement('div');
        card.className = 'card';
        card.style.borderLeft = '4px solid #aaa';
        card.style.opacity = '0.85';

        let actionButton = '';
        if (canManage) {
            actionButton = `
                <button class="btn-action-edit" style="padding: 5px; font-size: 0.8rem; background: rgba(37,99,235,0.08); border-color: rgba(37,99,235,0.3); color: #2563eb;" title="Reativar Turma" onclick="activateClass(${cls.id}, '${cls.nome}', event)">
                    <i class="fas fa-check-circle"></i>
                </button>
            `;
        }

        card.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h4 style="margin: 0; font-size: 1.1rem; color: #777;">
                        <i class="fas fa-ban" style="font-size:0.85rem; margin-right:5px; color:#aaa;"></i>${cls.nome}
                    </h4>
                    <span style="font-size: 0.8rem; color: #aaa;">Desativada em ${new Date(cls.created_at).toLocaleDateString('pt-BR')}</span>
                </div>
                <div>
                    ${actionButton}
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

/**
 * Renderiza a seção de turmas inativas.
 */
function renderInactiveClassesSection(classes) {
    const container = document.getElementById('inactive-classes-section');
    if (!container) return;

    renderInactiveClassesGrid(classes);
    updateInactiveSectionVisibility();
}

/**
 * Alterna a visibilidade da seção de turmas inativas e da barra de busca correspondente.
 */
window.toggleInactiveClassesSection = function () {
    isInactiveSectionVisible = !isInactiveSectionVisible;
    updateInactiveSectionVisibility();
}

/**
 * Atualiza os elementos visuais de controle baseando-se em isInactiveSectionVisible.
 */
function updateInactiveSectionVisibility() {
    const container = document.getElementById('inactive-classes-section');
    const searchContainer = document.getElementById('inactive-search-container');
    const btnText = document.getElementById('btn-toggle-inactive-text');
    const btnIcon = document.querySelector('#btn-toggle-inactive i');

    if (!container) return;

    if (isInactiveSectionVisible) {
        container.style.display = 'block';
        if (searchContainer) searchContainer.style.display = 'block';
        if (btnText) btnText.innerText = 'Ocultar Desativadas';
        if (btnIcon) {
            btnIcon.className = 'fas fa-eye';
        }
    } else {
        container.style.display = 'none';
        if (searchContainer) searchContainer.style.display = 'none';
        if (btnText) btnText.innerText = 'Ver Desativadas';
        if (btnIcon) {
            btnIcon.className = 'fas fa-eye-slash';
        }
        // Reseta busca ao ocultar
        const searchInput = document.getElementById('inactive-classes-search');
        if (searchInput && searchInput.value) {
            searchInput.value = '';
            renderInactiveClassesGrid(inactiveClassesCache);
        }
    }
}

/**
 * Filtro local para busca em turmas desativadas.
 */
window.searchInactiveClasses = function () {
    const query = (document.getElementById('inactive-classes-search')?.value || '').toLowerCase().trim();
    if (!query) {
        renderInactiveClassesGrid(inactiveClassesCache);
        return;
    }
    const filtered = inactiveClassesCache.filter(cls => cls.nome.toLowerCase().includes(query));
    renderInactiveClassesGrid(filtered);
}

/**
 * Busca client-side: filtra cards de turma pelo nome sem nova requisição.
 */
function searchClasses() {
    const query = (document.getElementById('classes-search')?.value || '').toLowerCase().trim();
    if (!query) {
        renderClassesGrid(classesCache);
        return;
    }
    const filtered = classesCache.filter(cls => cls.nome.toLowerCase().includes(query));
    renderClassesGrid(filtered);
}



function updateClassSelects(classes) {
    const regSelect = document.getElementById('reg-matricula');
    if (regSelect) {
        regSelect.innerHTML = '<option value="" disabled selected>Selecione a Turma</option>';
        classes.forEach(cls => {
            const opt = document.createElement('option');
            opt.value = cls.nome;
            opt.innerText = cls.nome;
            regSelect.appendChild(opt);
        });
    }
}

function openClassModal() {
    document.getElementById('modal-class').classList.add('active');
    document.getElementById('class-id').value = '';
    document.getElementById('class-name').value = '';
    setTimeout(() => document.getElementById('class-name').focus(), 100);
}

function closeClassModal() {
    document.getElementById('modal-class').classList.remove('active');
}

async function saveClass() {
    const name = document.getElementById('class-name').value.trim();
    if (!name) return showToast("Insira um nome", "error");

    try {
        const res = await fetch('api.php?action=create_class', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome: name })
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, "success");
            closeClassModal();
            loadClasses();
        } else {
            showToast(data.message, "error");
        }
    } catch (err) {
        showToast("Erro de rede ao salvar turma", "error");
    }
}

function openManageClassModal(className, classId, event) {
    if (event) event.stopPropagation();
    currentManageClass = { name: className, id: classId };

    document.getElementById('manage-class-title').innerText = `Gerenciar Turma: ${className}`;
    document.getElementById('modal-manage-class').classList.add('active');

    refreshStudentsInClassModal();
}

function closeManageClassModal() {
    document.getElementById('modal-manage-class').classList.remove('active');
    currentManageClass = null;
}

function refreshStudentsInClassModal() {
    if (!window.knownStudentsCache || !currentManageClass) return;

    const allStudents = window.knownStudentsCache;
    const enrolled = allStudents.filter(s => s.turma === currentManageClass.name);
    const notEnrolled = allStudents.filter(s => s.turma !== currentManageClass.name);

    const tbody = document.getElementById('class-students-body');
    tbody.innerHTML = '';

    enrolled.forEach(s => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${s.nome}</td>
            <td style="text-align: right;">
                <button class="btn-action btn-delete" title="Remover aluno desta turma" onclick="removeStudentFromClass(${s.id}, '${s.nome}')">
                    <i class="fas fa-user-minus"></i> Desvincular
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    if (enrolled.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" style="text-align:center; color:#666;">Nenhum aluno nesta turma.</td></tr>';
    }

    const available = allStudents.filter(s => !s.turma || s.turma === 'Sem Turma' || s.turma === 'N/A');

    const select = document.getElementById('select-add-student');
    select.innerHTML = '<option value="" disabled selected>Selecione um aluno livre...</option>';
    available.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.innerText = `${s.nome}`;
        select.appendChild(opt);
    });
}

async function addStudentToClass() {
    if (!currentManageClass) return;
    const select = document.getElementById('select-add-student');
    const studentId = select.value;

    if (!studentId) return showToast("Selecione um aluno", "error");

    await updateStudentClassAPI(studentId, currentManageClass.name);
}

async function removeStudentFromClass(studentId, studentName) {
    const confirmed = await window.openConfirmModal("Desvincular Aluno", `Tirar ${studentName} desta turma? Ele ficará 'Sem Turma'.`, "Sim, Desvincular");
    if (!confirmed) return;
    await updateStudentClassAPI(studentId, 'Sem Turma');
}

async function updateStudentClassAPI(studentId, newClassName) {
    try {
        const res = await fetch('api.php?action=update_student_class', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: studentId, className: newClassName })
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, "success");
            const st = window.knownStudentsCache.find(s => s.id == studentId);
            if (st) st.turma = newClassName;
            refreshStudentsInClassModal();
            loadStudents();
        } else {
            showToast(data.message, "error");
        }
    } catch (err) {
        showToast("Erro na modificação do aluno", "error");
    }
}

/**
 * Desativa a turma sendo gerenciada atualmente no modal.
 */
window.deleteCurrentClass = async function () {
    if (!currentManageClass) return;
    await window.deactivateClassDirect(currentManageClass.id, currentManageClass.name);
}

/**
 * Desativa a turma diretamente pelo Card (soft delete).
 */
window.deactivateClassDirect = async function (id, name, event) {
    if (event) event.stopPropagation();

    const confirmed = await window.openConfirmModal(
        "Desativar Turma",
        `Deseja desativar a turma "${name}"?\nOs alunos desta turma também serão desativados e a turma ficará inativa.\nVocê poderá reativá-la depois.`,
        "Sim, Desativar"
    );
    if (!confirmed) return;

    try {
        const res  = await fetch(`${window.API_URL}?action=deactivate_class&id=${id}`);
        const data = await res.json();

        if (data.success) {
            showToast(data.message, "success");
            closeManageClassModal();
            window.knownStudentsCache = null;
            if (typeof loadStudents === 'function') await loadStudents();
            loadClasses();
        } else {
            showToast(data.message, "error");
        }
    } catch (err) {
        showToast("Erro ao desativar", "error");
    }
}

/**
 * Alias antigo para manter compatibilidade com botões existentes.
 */
window.deleteClassDirect = window.deactivateClassDirect;

/**
 * Ativa uma turma desativada.
 */
window.activateClass = async function (id, name, event) {
    if (event) event.stopPropagation();

    const confirmed = await window.openConfirmModal(
        "Reativar Turma",
        `Deseja reativar a turma "${name}"?\nEla voltará a aparecer na lista de turmas ativas.`,
        "Sim, Reativar"
    );
    if (!confirmed) return;

    try {
        const res  = await fetch(`${window.API_URL}?action=activate_class&id=${id}`);
        const data = await res.json();

        if (data.success) {
            showToast(data.message, "success");
            loadClasses();
        } else {
            showToast(data.message, "error");
        }
    } catch (err) {
        showToast("Erro ao reativar turma", "error");
    }
}

/**
 * Edita o nome da turma diretamente pelo Card.
 */
window.editClass = async function (id, currentName, event) {
    if (event) event.stopPropagation();

    const newName = await window.openEditModal("Editar Turma", "Digite o novo nome da turma:", currentName);
    if (!newName || newName.trim() === "" || newName === currentName) return;

    try {
        const res = await fetch(`${window.API_URL}?action=update_class`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, nome: newName.trim() })
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, "success");
            window.knownStudentsCache = null;
            if (typeof loadStudents === 'function') await loadStudents();
            loadClasses();
        } else {
            showToast(data.message || "Erro ao editar.", "error");
        }
    } catch (err) {
        showToast("Erro de rede ao editar.", "error");
    }
}

/**
 * Exibe a seção de alunos da turma selecionada e esconde o grid de turmas.
 */
window.showClassStudentsView = function (className, classId, event) {
    if (event) event.stopPropagation();
    currentManageClass = { name: className, id: classId };

    document.getElementById('classes-list-view').style.display = 'none';
    document.getElementById('class-detail-view').style.display = 'block';

    const manageable = canManageCurrentClass();
    
    const btnNewSt = document.getElementById('btn-new-student');
    const btnImpSt = document.getElementById('btn-import-students');
    const btnDeacCl = document.getElementById('btn-deactivate-class');
    const containerVinc = document.getElementById('vincular-student-container');

    if (btnNewSt)  btnNewSt.style.display = manageable ? 'flex' : 'none';
    if (btnImpSt)  btnImpSt.style.display = manageable ? 'flex' : 'none';
    if (btnDeacCl) btnDeacCl.style.display = manageable ? 'flex' : 'none';
    if (containerVinc) containerVinc.style.display = manageable ? 'flex' : 'none';

    document.getElementById('class-detail-title').innerText = manageable ? `Gestão de Alunos: ${className}` : `Visualização de Alunos: ${className}`;

    document.getElementById('class-student-search').value = '';
    document.getElementById('class-student-facial-filter').value = 'all';

    refreshStudentsDetailTable();
}

/**
 * Retorna à visualização em grid das turmas.
 */
window.backToClassesGrid = function () {
    document.getElementById('class-detail-view').style.display = 'none';
    document.getElementById('classes-list-view').style.display = 'block';
    currentManageClass = null;
}

/**
 * Atualiza os dados da tabela de detalhes e o dropdown de vinculação.
 */
window.refreshStudentsDetailTable = function () {
    if (!window.knownStudentsCache || !currentManageClass) return;

    const allStudents = window.knownStudentsCache;
    const enrolled = allStudents.filter(s => s.turma === currentManageClass.name);

    renderClassStudentsTable(enrolled);

    const available = allStudents.filter(s => !s.turma || s.turma === 'Sem Turma' || s.turma === 'N/A' || s.turma === 'N/A ');
    const select = document.getElementById('select-add-student-detail');
    if (select) {
        select.innerHTML = '<option value="" disabled selected>Vincular aluno...</option>';
        available.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.innerText = s.nome;
            select.appendChild(opt);
        });
    }
}

/**
 * Renderiza a tabela aplicando busca textual e filtros de biometria facial.
 */
window.renderClassStudentsTable = function (students) {
    const tbody = document.getElementById('class-students-detail-body');
    if (!tbody) return;

    tbody.innerHTML = '';

    const searchQuery  = (document.getElementById('class-student-search')?.value || '').toLowerCase().trim();
    const facialFilter = document.getElementById('class-student-facial-filter')?.value || 'all';

    const filtered = students.filter(s => {
        const matchesSearch = s.nome.toLowerCase().includes(searchQuery);
        const hasFacial = s.face_descriptor && s.face_descriptor.trim() !== '';
        let matchesFacial = true;
        if (facialFilter === 'yes') {
            matchesFacial = hasFacial;
        } else if (facialFilter === 'no') {
            matchesFacial = !hasFacial;
        }
        return matchesSearch && matchesFacial;
    });

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#666; padding: 2rem;">Nenhum aluno corresponde aos filtros.</td></tr>';
        return;
    }

    filtered.forEach(s => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #eee';

        const hasFacial = s.face_descriptor && s.face_descriptor.trim() !== '';
        const facialBadge = hasFacial
            ? `<span class="badge badge-success" style="background-color: #2ec4b6; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-check-circle"></i> Cadastrado</span>`
            : `<span class="badge badge-gray" style="background-color: #e5e5e5; color: #666; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-times-circle"></i> Não Cadastrado</span>`;

        const facialBtnClass = hasFacial ? 'btn-delete' : 'btn-primary';
        const facialBtnStyle = hasFacial ? 'background-color: #dc3545; color: #fff; border: none;' : '';
        const facialBtnIcon  = hasFacial ? 'fa-trash' : 'fa-camera';
        const facialBtnText  = hasFacial ? 'Remover Biometria' : 'Cadastrar Facial';

        const manageable = canManageCurrentClass();
        let actionsHtml = '';
        if (manageable) {
            actionsHtml = `
                <button class="btn-facial-action ${facialBtnClass} btn-sm"
                    data-student-id="${s.id}"
                    style="padding: 4px 8px; font-size: 0.8rem; border-radius: 4px; cursor: pointer; ${facialBtnStyle}">
                    <i class="fas ${facialBtnIcon}"></i> ${facialBtnText}
                </button>
                <button class="btn-action btn-delete btn-desvincular-action"
                    data-student-id="${s.id}"
                    style="padding: 4px 8px; font-size: 0.8rem; border-radius: 4px; display: flex; align-items: center; gap: 4px;"
                    title="Desvincular Aluno">
                    <i class="fas fa-user-minus"></i> Desvincular
                </button>
            `;
        } else {
            actionsHtml = `<span style="color:#aaa; font-size:0.85rem; font-style:italic;">Sem permissões</span>`;
        }

        tr.innerHTML = `
            <td style="padding: 12px 16px; font-weight: 500; color: var(--text);">${s.nome}</td>
            <td style="padding: 12px 16px; text-align: center;">${facialBadge}</td>
            <td style="padding: 12px 16px; text-align: right; display: flex; gap: 8px; justify-content: flex-end; align-items: center; border: none;">
                ${actionsHtml}
            </td>
        `;

        if (manageable) {
            tr.querySelector('.btn-facial-action').addEventListener('click', () => {
                if (hasFacial) {
                    removeStudentBiometrics(s.id, s.nome);
                } else {
                    startFacialEnrollmentForStudent(s.id, s.nome);
                }
            });
            tr.querySelector('.btn-desvincular-action').addEventListener('click', () => {
                removeStudentFromClassFromDetail(s.id, s.nome);
            });
        }

        tbody.appendChild(tr);
    });
}

/**
 * Remove a biometria facial de um aluno após confirmação.
 */
async function removeStudentBiometrics(studentId, studentName) {
    const confirmed = await window.openConfirmModal(
        "Remover Biometria",
        `Deseja realmente excluir a biometria facial de "${studentName}"?\nEle não conseguirá acessar a biblioteca por reconhecimento facial até que seja cadastrado novamente.`,
        "Sim, Remover"
    );
    if (!confirmed) return;

    showToast("Removendo biometria...", "info");

    try {
        const res = await fetch('api.php?action=update_student_biometrics', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: studentId,
                face_descriptor: null,
                face_landmarks: null
            })
        });
        const data = await res.json();

        if (data.success) {
            showToast("Biometria removida com sucesso!", "success");

            if (window.knownStudentsCache) {
                const cached = window.knownStudentsCache.find(s => s.id == studentId);
                if (cached) {
                    cached.face_descriptor = null;
                    cached.face_landmarks  = null;
                }
            }

            if (typeof loadStudents === 'function') {
                await loadStudents();
            }
            refreshStudentsDetailTable();
        } else {
            showToast(data.message || "Erro ao remover biometria.", "error");
        }
    } catch (err) {
        showToast("Erro de rede ao remover biometria.", "error");
        console.error(err);
    }
}

/**
 * Filtra a tabela localmente com base na busca textual e filtro biométrico.
 */
window.filterClassStudents = function () {
    if (!window.knownStudentsCache || !currentManageClass) return;

    const enrolled = window.knownStudentsCache.filter(s => s.turma === currentManageClass.name);
    renderClassStudentsTable(enrolled);
}

/**
 * Vincula aluno à turma ativa a partir da seção de detalhes.
 */
window.addStudentToClassFromDetail = async function () {
    if (!currentManageClass) return;
    const select    = document.getElementById('select-add-student-detail');
    const studentId = select.value;

    if (!studentId) return showToast("Selecione um aluno", "error");

    await updateStudentClassAPIDetail(studentId, currentManageClass.name);
}

/**
 * Desvincula o aluno selecionado colocando-o em 'Sem Turma'.
 */
window.removeStudentFromClassFromDetail = async function (studentId, studentName) {
    const confirmed = await window.openConfirmModal("Desvincular Aluno", `Tirar ${studentName} desta turma? Ele ficará 'Sem Turma'.`, "Sim, Desvincular");
    if (!confirmed) return;
    await updateStudentClassAPIDetail(studentId, 'Sem Turma');
}

/**
 * Atualiza a turma do aluno via API e atualiza a interface local.
 */
async function updateStudentClassAPIDetail(studentId, newClassName) {
    try {
        const res = await fetch('api.php?action=update_student_class', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: studentId, className: newClassName })
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, "success");
            const st = window.knownStudentsCache.find(s => s.id == studentId);
            if (st) st.turma = newClassName;
            refreshStudentsDetailTable();
            if (typeof loadStudents === 'function') {
                loadStudents();
            }
        } else {
            showToast(data.message, "error");
        }
    } catch (err) {
        showToast("Erro na modificação do aluno", "error");
    }
}

/**
 * Desativa a turma de dentro da visualização dedicada de detalhes.
 */
window.deleteCurrentClassFromDetail = async function () {
    if (!currentManageClass) return;
    const id   = currentManageClass.id;
    const name = currentManageClass.name;

    const confirmed = await window.openConfirmModal(
        "Desativar Turma",
        `Deseja desativar a turma "${name}"?\nOs alunos desta turma também serão desativados. Você poderá reativá-la depois.`,
        "Sim, Desativar"
    );
    if (!confirmed) return;

    try {
        const res  = await fetch(`api.php?action=deactivate_class&id=${id}`);
        const data = await res.json();

        if (data.success) {
            showToast(data.message, "success");
            backToClassesGrid();
            window.knownStudentsCache = null;
            if (typeof loadStudents === 'function') await loadStudents();
            loadClasses();
        } else {
            showToast(data.message, "error");
        }
    } catch (err) {
        showToast("Erro ao desativar", "error");
    }
}

window.triggerExcelImport = function () {
    document.getElementById('excel-import-file').click();
}

window.handleExcelImport = async function (event) {
    const file = event.target.files[0];
    if (!file || !currentManageClass) return;

    event.target.value = '';

    if (!(/(\.xlsx|\.xls|\.csv)$/i.test(file.name))) {
        showToast("Arquivo inválido. Envie apenas planilhas nos formatos .xlsx, .xls ou .csv", "error");
        return;
    }

    if (file.size === 0) {
        showToast("O arquivo selecionado está vazio.", "error");
        return;
    }

    const fileKey = `${currentManageClass.name}::${file.name}::${file.size}`;
    if (_importedFilesHistory[fileKey]) {
        showToast("Este arquivo já está sendo processado. Aguarde.", "info");
        return;
    }

    const formData = new FormData();
    formData.append('excel_file', file);
    formData.append('className', currentManageClass.name);

    _importedFilesHistory[fileKey] = true;

    showToast("Importando alunos via planilha...", "info");

    let data = null;
    try {
        const res = await fetch('api.php?action=import_students_excel', {
            method: 'POST',
            body: formData
        });

        const rawText = await res.text();
        try {
            data = JSON.parse(rawText);
        } catch (parseErr) {
            console.error("Resposta inválida do servidor:", rawText);
            delete _importedFilesHistory[fileKey];
            showToast("Resposta inesperada do servidor. Verifique os logs.", "error");
            return;
        }
    } catch (networkErr) {
        console.error("Erro de rede ao importar:", networkErr);
        delete _importedFilesHistory[fileKey];
        showToast("Falha de conexão ao tentar importar. Tente novamente.", "error");
        return;
    }

    delete _importedFilesHistory[fileKey];

    if (data && data.success) {
        window.knownStudentsCache = null;
        if (typeof loadStudents === 'function') {
            await loadStudents();
        }
        refreshStudentsDetailTable();

        _showImportResultModal(data, currentManageClass.name);
    } else if (data) {
        const toastType = data.code === 'already_imported' ? 'warning' : 'error';
        showToast(data.message || "Erro ao processar a importação.", toastType);
    }
}

/**
 * Monta e exibe o modal de resultado da importação com resumo completo.
 */
function _showImportResultModal(data, className) {
    const imported   = data.imported    ?? 0;
    const duplicates = data.duplicates  ?? 0;
    const inOtherCls = data.in_other_classes  ?? 0;
    const dupNames   = data.duplicate_names   ?? [];
    const otherClsNames = data.in_other_class_names ?? [];

    let bodyHtml = '';

    if (imported === 0 && duplicates === 0) {
        bodyHtml += `
            <div style="text-align:center; padding: 0.5rem 0 1rem;">
                <i class="fas fa-info-circle" style="font-size:2rem; color:#aaa; margin-bottom:0.75rem; display:block;"></i>
                <span style="font-size:1rem; color:#555;">Nenhum aluno novo foi encontrado neste arquivo para a turma <strong>${className}</strong>.</span>
            </div>`;
    } else {
        bodyHtml += `
            <div style="text-align:center; margin-bottom:1.25rem;">
                <span style="font-size:1.05rem; color:var(--text-dark,#333);">
                    ${imported > 0
                ? `<strong>${imported}</strong> aluno(s) cadastrado(s) com sucesso na turma <strong>${className}</strong>.`
                : `Nenhum aluno novo foi adicionado à turma <strong>${className}</strong>.`}
                </span>
            </div>`;
    }

    if (duplicates > 0) {
        const listHtml = dupNames.map(n => `<li style="margin-bottom:3px;">${n}</li>`).join('');
        bodyHtml += `
            <div style="background:rgba(243,156,18,0.08); border-left:4px solid #f39c12; padding:12px; border-radius:8px; margin-bottom:1rem;">
                <span style="color:#d35400; font-weight:700; font-size:0.9rem; display:block; margin-bottom:4px;">
                    <i class="fas fa-clone"></i> Já existem nesta turma — ignorados (${duplicates}):
                </span>
                <p style="font-size:0.82rem; color:#666; margin:0 0 8px;">Esses nomes já estavam cadastrados nesta turma e não foram duplicados:</p>
                <div style="max-height:110px; overflow-y:auto; background:#fcfcfc; border:1px solid #e2e8f0; border-radius:6px; padding:10px;">
                    <ul style="margin:0; padding-left:1.2rem; font-size:0.88rem; color:#555;">${listHtml}</ul>
                </div>
            </div>`;
    }

    if (inOtherCls > 0) {
        const listHtml = otherClsNames.map(n => `<li style="margin-bottom:3px;">${n}</li>`).join('');
        bodyHtml += `
            <div style="background:rgba(231,76,60,0.07); border-left:4px solid #e74c3c; padding:12px; border-radius:8px; margin-bottom:0.5rem;">
                <span style="color:#c0392b; font-weight:700; font-size:0.9rem; display:block; margin-bottom:4px;">
                    <i class="fas fa-exclamation-triangle"></i> Nomes já existentes no sistema (${inOtherCls}):
                </span>
                <p style="font-size:0.82rem; color:#666; margin:0 0 8px;">
                    Os alunos abaixo <strong>já estavam cadastrados em outra turma</strong> no sistema e foram adicionados também a <strong>${className}</strong>.
                    Verifique se não é um cadastro duplicado:
                </p>
                <div style="max-height:130px; overflow-y:auto; background:#fcfcfc; border:1px solid #fcc; border-radius:6px; padding:10px;">
                    <ul style="margin:0; padding-left:1.2rem; font-size:0.88rem; color:#555;">${listHtml}</ul>
                </div>
            </div>`;
    }

    if (duplicates > 0 && imported === 0 && inOtherCls === 0) {
        bodyHtml += `
            <div style="text-align:center; padding: 0.5rem 0;">
                <p style="font-size:0.9rem; color:#888; margin:0;">
                    Todos os nomes da planilha já estavam cadastrados nesta turma. Nenhuma alteração foi feita.
                </p>
            </div>`;
    }

    const hasWarnings = duplicates > 0 || inOtherCls > 0;
    const titleText = imported > 0
        ? (hasWarnings ? 'Importação com Avisos' : 'Importação Concluída')
        : 'Sem Alterações';

    window.openImportResultModal(titleText, bodyHtml, imported > 0 || !hasWarnings);
}

/**
 * Adiciona um aluno novo manualmente na turma ativa.
 */
window.addNewStudentToCurrentClass = async function () {
    if (!currentManageClass) return;

    const studentName = await window.openEditModal("Adicionar Aluno", "Digite o nome do novo aluno que deseja cadastrar nesta turma:");
    if (!studentName || studentName.trim() === "") return;

    showToast("Adicionando aluno...", "info");

    try {
        const res = await fetch('api.php?action=register_student', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nome: studentName.trim(),
                turma: currentManageClass.name,
                face_descriptor: null,
                face_landmarks: null
            })
        });
        const data = await res.json();

        if (data.success) {
            showToast("Aluno cadastrado com sucesso!", "success");
            window.knownStudentsCache = null;
            if (typeof loadStudents === 'function') {
                await loadStudents();
            }
            refreshStudentsDetailTable();
        } else {
            showToast(data.message || "Erro ao cadastrar aluno.", "error");
        }
    } catch (err) {
        showToast("Erro de rede ao cadastrar aluno.", "error");
        console.error(err);
    }
}

/**
 * Abre o modal de permissões da turma, carregando os professores e suas permissões atuais.
 * Pode ser chamado do grid de turmas ou do botão de detalhes da turma.
 */
window.openClassPermissionsModal = async function (classId, className, event) {
    if (event) event.stopPropagation();

    if (classId && className) {
        currentManageClass = { id: classId, name: className };
    }

    if (!currentManageClass || !currentManageClass.id) {
        showToast("Selecione uma turma primeiro.", "error");
        return;
    }

    const listDiv = document.getElementById('class-permissions-list');
    if (listDiv) {
        listDiv.innerHTML = `
            <div style="text-align: center; color: #999; padding: 1rem;">
                <i class="fas fa-spinner fa-spin"></i> Carregando professores...
            </div>`;
    }

    const modal = document.getElementById('modal-class-permissions');
    if (modal) {
        modal.classList.add('active');
    }

    try {
        const response = await fetch(`api.php?action=list_class_permissions&turma_id=${currentManageClass.id}`);
        const professores = await response.json();

        if (listDiv) {
            listDiv.innerHTML = '';
            if (Array.isArray(professores) && professores.length > 0) {
                professores.forEach(prof => {
                    const row = document.createElement('label');
                    row.style.display = 'flex';
                    row.style.alignItems = 'center';
                    row.style.gap = '10px';
                    row.style.padding = '8px 12px';
                    row.style.borderRadius = '6px';
                    row.style.cursor = 'pointer';
                    row.style.transition = 'background-color 0.2s';
                    
                    row.onmouseover = () => { row.style.backgroundColor = '#f7f7f7'; };
                    row.onmouseout = () => { row.style.backgroundColor = 'transparent'; };

                    const checked = prof.autorizado ? 'checked' : '';
                    row.innerHTML = `
                        <input type="checkbox" class="class-prof-permission" value="${prof.id}" ${checked} style="cursor: pointer; width: 16px; height: 16px;">
                        <span style="font-size: 0.9rem; font-weight: 600; color: #444; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-chalkboard-teacher" style="color: #666; font-size: 0.85rem;"></i> ${prof.usuario}
                        </span>
                    `;
                    listDiv.appendChild(row);
                });
            } else {
                listDiv.innerHTML = '<div style="text-align:center; color:#999; padding:1.5rem;">Nenhum professor ativo cadastrado.</div>';
            }
        }
    } catch (err) {
        console.error("Erro ao carregar permissões:", err);
        if (listDiv) {
            listDiv.innerHTML = '<div style="text-align:center; color:var(--error); padding:1.5rem;">Erro ao carregar lista.</div>';
        }
    }
};

/**
 * Fecha a janela modal de permissões.
 */
window.closeClassPermissionsModal = function () {
    const modal = document.getElementById('modal-class-permissions');
    if (modal) {
        modal.classList.remove('active');
    }
};

/**
 * Envia as permissões selecionadas para serem salvas no backend.
 */
window.saveClassPermissions = async function () {
    if (!currentManageClass || !currentManageClass.id) return;

    const checkboxes = document.querySelectorAll('.class-prof-permission:checked');
    const professoresIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    try {
        const response = await fetch('api.php?action=save_class_permissions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                turma_id: currentManageClass.id,
                professores: professoresIds
            })
        });

        const data = await response.json();
        if (data.success) {
            showToast("Permissões salvas com sucesso!", "success");
            closeClassPermissionsModal();
            loadClasses(); // Recarrega as turmas para atualizar permissões no front
        } else {
            showToast(data.message || "Erro ao salvar permissões.", "error");
        }
    } catch (err) {
        console.error("Erro ao salvar permissões:", err);
        showToast("Erro de comunicação ao salvar.", "error");
    }
};
