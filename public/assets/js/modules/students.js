/**
 * Alunos: CRUD, Modais e Filtros
 */

function openRegistration(descriptor = null) {
    const desc = descriptor || window.lastFaceDescriptor;

    // Reset registration form state
    document.getElementById('registration-form')?.reset();

    // Load turmas dynamically
    if (typeof window.loadTurmasForSelect === 'function') {
        window.loadTurmasForSelect();
    }

    const regDesc = document.getElementById('reg-descriptor');
    const regFaceData = document.getElementById('reg-face-data');
    const captureStatus = document.getElementById('face-capture-status');
    const captureCount = document.getElementById('face-capture-count');

    if (desc) {
        // Legacy: single descriptor from live recognition
        if (regDesc) regDesc.value = JSON.stringify(Array.from(desc));
        if (captureStatus) {
            captureStatus.innerText = "[Biometria Facial Capturada ✓]";
            captureStatus.style.color = "var(--success)";
        }
        if (captureCount) captureCount.innerText = "1/1 (captura rápida)";
    } else {
        // No descriptor yet: user needs to use the enrollment wizard
        if (regDesc) regDesc.value = '';
        if (regFaceData) regFaceData.value = '';
        if (captureStatus) {
            captureStatus.innerText = "[Biometria Indisponível]";
            captureStatus.style.color = "var(--error)";
        }
        if (captureCount) captureCount.innerText = "Fotos capturadas: 0/3";
    }

    document.getElementById('modal-register').classList.add('active');
}

function closeRegistration() {
    document.getElementById('modal-register').classList.remove('active');
    if (typeof window.startVideo === 'function') {
        setTimeout(() => window.startVideo(), 500);
    }
}

window.loadTurmasForSelect = async function () {
    const select = document.getElementById('reg-matricula');
    if (!select) return;

    select.innerHTML = '<option value="" disabled selected>Carregando Turmas...</option>';
    try {
        const response = await fetch(`${window.API_URL}?action=list_classes`);
        const turmas = await response.json();
        select.innerHTML = '<option value="" disabled selected>Selecione a Turma</option>';
        if (Array.isArray(turmas)) {
            turmas.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.nome;
                opt.textContent = t.nome;
                select.appendChild(opt);
            });
        }
    } catch (e) {
        select.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
    }
}

async function saveRegistration() {
    const name = document.getElementById('reg-name').value;
    const turma = document.getElementById('reg-matricula').value;
    const descriptor = document.getElementById('reg-descriptor').value;
    const faceDataRaw = document.getElementById('reg-face-data')?.value;

    if (!descriptor) {
        showToast("Biometria facial não detectada. Use o botão 'Capturar Biometria'.", "error");
        return;
    }

    if (!name || !turma) {
        showToast("Preencha todos os campos do formulário.", "error");
        return;
    }

    // Build payload
    const payload = {
        nome: name,
        turma: turma,
        face_descriptor: descriptor
    };

    // Include landmarks/enrollment data if available
    if (faceDataRaw) {
        try {
            const faceData = JSON.parse(faceDataRaw);
            payload.face_landmarks = JSON.stringify({
                landmarks: faceData.landmarks,
                captureCount: faceData.captureCount,
                allDescriptors: faceData.allDescriptors,
                images: faceData.images
            });
        } catch (e) {
            console.warn('Could not parse face enrollment data.');
        }
    }

    try {
        const response = await fetch(`${window.API_URL}?action=register_student`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {
            showToast(`Usuário ${name} registrado com sucesso!`, 'success');
            window.knownStudentsCache = null;
            loadStudents();
            closeRegistration();
            document.getElementById('registration-form').reset();
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        showToast('Falha na comunicação com o servidor.', 'error');
    }
}

window.showingInactive = false;

window.toggleInactiveMode = function () {
    window.showingInactive = !window.showingInactive;
    const btn = document.getElementById('toggle-inactive-btn');
    if (btn) {
        if (window.showingInactive) {
            btn.innerHTML = '<i class="fas fa-user-check"></i> Ver Alunos Ativos';
            btn.style.borderColor = 'var(--success)';
            btn.style.color = 'var(--success)';
        } else {
            btn.innerHTML = '<i class="fas fa-user-slash"></i> Ver Alunos Inativos';
            btn.style.borderColor = '#6C757D';
            btn.style.color = '#6C757D';
        }
    }
    loadStudents();
};

async function loadStudents() {
    // Reset da seleção em lote ao recarregar a lista
    const masterCb = document.getElementById('select-all-students');
    if (masterCb) masterCb.checked = false;
    const btnDelete = document.getElementById('btn-delete-selected-students');
    if (btnDelete) btnDelete.style.display = 'none';

    try {
        const actionUrl = window.showingInactive
            ? `${window.API_URL}?action=list_inactive_students`
            : `${window.API_URL}?action=list_students`;
        const response = await fetch(actionUrl);
        const students = await response.json();

        if (Array.isArray(students)) {
            if (!window.showingInactive) {
                // Carrega ativos no cache com status marcado
                const activeWithStatus = students.map(s => ({ ...s, status: 'ativo' }));

                // Também carrega inativos no cache de reconhecimento (para bloqueá-los na câmera)
                try {
                    const inactiveRes = await fetch(`${window.API_URL}?action=list_inactive_students`);
                    const inactive = await inactiveRes.json();
                    if (Array.isArray(inactive)) {
                        const inactiveWithStatus = inactive.map(s => ({ ...s, status: 'inativo' }));
                        window.knownStudentsCache = [...activeWithStatus, ...inactiveWithStatus];
                    } else {
                        window.knownStudentsCache = activeWithStatus;
                    }
                } catch (_) {
                    window.knownStudentsCache = activeWithStatus;
                }
            }

            const tbody = document.getElementById('student-table-body');
            if (tbody) {
                tbody.innerHTML = '';
                students.forEach(user => {
                    const tr = document.createElement('tr');

                    if (window.showingInactive) {
                        tr.innerHTML = `
                            <td style="text-align: center;"><input type="checkbox" class="student-select" value="${user.id}" onclick="updateSelectedStudentsState()"></td>
                            <td style="text-align: center; font-weight: bold; color: #888;">${user.id}</td>
                            <td>
                                ${user.nome}
                                <button class="btn-action-edit" style="margin-left: 10px; font-size: 0.75rem; padding: 2px 6px; color: var(--success); border-color: var(--success); display: inline-flex; align-items: center; gap: 3px;" onclick="reactivateStudent(${user.id}, '${user.nome}')">
                                    <i class="fas fa-check"></i> Reativar
                                </button>
                            </td>
                            <td style="text-align: center;">${user.turma || 'N/A'}</td>
                            <td style="text-align: center; font-weight: bold; color: var(--text-gray);">
                                Inativo
                            </td>
                            <td>
                                <button class="btn-action-edit" style="color: var(--success); border-color: var(--success); margin-right: 5px; display: inline-flex; align-items: center; gap: 5px;" onclick="reactivateStudent(${user.id}, '${user.nome}')">
                                    <i class="fas fa-check"></i> Reativar
                                </button>
                            </td>
                        `;
                    } else {
                        tr.innerHTML = `
                            <td style="text-align: center;"><input type="checkbox" class="student-select" value="${user.id}" onclick="updateSelectedStudentsState()"></td>
                            <td style="text-align: center; font-weight: bold; color: #888;">${user.id}</td>
                            <td>${user.nome}</td>
                            <td style="text-align: center;">${user.turma || 'N/A'}</td>
                            <td style="text-align: center; font-weight: bold; color: var(--primary);">
                                ${user.last_entry ? window.safeFormatLocaleTimeString(user.last_entry) : 'Ausente'}
                            </td>
                            <td>
                                <!-- Botão de Editar Nome do Aluno -->
                                <button class="btn-action-edit" style="margin-right: 5px;" onclick="editStudent(${user.id}, '${user.nome}')"><i class="fas fa-edit"></i> Editar</button>
                                <!-- Botão de desativar Aluno -->
                                <button class="btn-action btn-delete" onclick="deleteStudent(${user.id}, '${user.nome}')"><i class="fas fa-trash"></i> Inativar</button>
                            </td>
                        `;
                    }
                    tbody.appendChild(tr);
                });
            }
        }
    } catch (err) {
        console.error("Erro ao listar usuários:", err);
    }
}

window.reactivateStudent = async function (id, name) {
    const confirmed = await window.openConfirmModal("Reativar Aluno", `Deseja reativar o cadastro de ${name}?`, "Sim, reativar");
    if (!confirmed) return;

    try {
        const response = await fetch(`${window.API_URL}?action=activate_student&id=${id}`);
        const result = await response.json();

        if (result.success) {
            showToast(`Usuário ${name} reativado com sucesso!`, 'success');
            window.knownStudentsCache = null;
            loadStudents();
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        showToast("Erro ao reativar o aluno.", 'error');
    }
};

/**
 * Função para Editar o nome de um aluno diretamente.
 * Solicita o novo nome via prompt e envia para a API.
 */
window.editStudent = async function (id, oldName) {
    const newName = await window.openEditModal("Editar Aluno", "Digite o novo nome do aluno:", oldName);
    if (!newName || newName.trim() === "" || newName === oldName) return;

    try {
        const response = await fetch(window.API_URL + '?action=update_student', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, nome: newName.trim() })
        });
        const result = await response.json();

        if (result.success) {
            showToast("Nome atualizado com sucesso.", 'success');
            window.knownStudentsCache = null; // Invalida o cache
            loadStudents(); // Recarrega a tabela
        } else {
            showToast(result.message || "Erro ao editar.", 'error');
        }
    } catch (err) {
        showToast("Erro na comunicação com a API.", 'error');
    }
}

/**
 * Função para excluir permanentemente um aluno e seus logs de acesso.
 */
window.deleteStudent = async function (id, name) {
    const confirmed = await window.openConfirmModal("Desativar Aluno", `Deseja inativar o cadastro de ${name}?`, "Sim, desativar");
    if (!confirmed) return;

    try {
        const response = await fetch(`${window.API_URL}?action=delete_student&id=${id}`);
        const result = await response.json();

        if (result.success) {
            showToast("Removido com sucesso.", 'success');
            window.knownStudentsCache = null;
            loadStudents();
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        showToast("Erro na exclusão.", 'error');
    }
}

function filterStudents() {
    const inputName = document.getElementById("search-student-name");
    const inputClass = document.getElementById("search-student-class");
    const filterName = inputName ? inputName.value.toUpperCase() : "";
    const filterClass = inputClass ? inputClass.value.toUpperCase() : "";

    const tbody = document.getElementById("student-table-body");
    if (!tbody) return;
    const trs = tbody.getElementsByTagName("tr");

    for (let i = 0; i < trs.length; i++) {
        let tdName = trs[i].getElementsByTagName("td")[1];
        let tdClass = trs[i].getElementsByTagName("td")[2];

        if (tdName && tdClass) {
            let txtName = tdName.textContent || tdName.innerText;
            let txtClass = tdClass.textContent || tdClass.innerText;

            const nameMatch = txtName.toUpperCase().indexOf(filterName) > -1;
            const classMatch = txtClass.toUpperCase().indexOf(filterClass) > -1;

            if (nameMatch && classMatch) {
                trs[i].style.display = "";
            } else {
                trs[i].style.display = "none";
            }
        }
    }
}

// Inicializa a higienização de inputs para o campo de cadastro de alunos
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.setupInputSanitizer === 'function') {
        window.setupInputSanitizer('#reg-name', 'name');
    }
});

/**
 * Alterna a seleção de todos os alunos na tabela com base no checkbox mestre.
 */
window.toggleAllStudents = function (masterCb) {
    const checkboxes = document.querySelectorAll('.student-select');
    checkboxes.forEach(cb => {
        // Apenas marcar se a linha estiver visível (não filtrada)
        const tr = cb.closest('tr');
        if (tr && tr.style.display !== 'none') {
            cb.checked = masterCb.checked;
        }
    });
    window.updateSelectedStudentsState();
};

/**
 * Atualiza a visibilidade e o visual do botão de ação em lote com base nas seleções.
 */
window.updateSelectedStudentsState = function () {
    const selected = document.querySelectorAll('.student-select:checked');
    const btn = document.getElementById('btn-delete-selected-students');
    if (!btn) return;

    if (selected.length > 0) {
        btn.style.display = 'inline-flex';
        
        // Atualiza a aparência do botão dependendo do modo ativo/inativo
        if (window.showingInactive) {
            btn.innerHTML = `<i class="fas fa-check"></i> Reativar Selecionados (${selected.length})`;
            btn.style.backgroundColor = 'var(--success)';
        } else {
            btn.innerHTML = `<i class="fas fa-user-slash"></i> Desativar Selecionados (${selected.length})`;
            btn.style.backgroundColor = '#dc3545';
        }
    } else {
        btn.style.display = 'none';
    }
};

/**
 * Executa a ação de desativação ou reativação em lote dos alunos selecionados.
 */
window.deleteSelectedStudents = async function () {
    const selected = document.querySelectorAll('.student-select:checked');
    if (selected.length === 0) return;

    const ids = Array.from(selected).map(cb => cb.value);
    const count = ids.length;

    const title = window.showingInactive ? "Reativar Alunos em Lote" : "Desativar Alunos em Lote";
    const msg = window.showingInactive 
        ? `Deseja reativar o cadastro dos ${count} aluno(s) selecionado(s)?` 
        : `Deseja inativar o cadastro dos ${count} aluno(s) selecionado(s)?`;
    const confirmBtnText = window.showingInactive ? "Sim, reativar" : "Sim, desativar";

    const confirmed = await window.openConfirmModal(title, msg, confirmBtnText);
    if (!confirmed) return;

    const action = window.showingInactive ? 'activate_student' : 'delete_student';
    
    // Mostra um toast informativo do início do processo
    showToast(`Processando ação em lote para ${count} aluno(s)...`, 'info');

    let successes = 0;
    let failures = 0;

    try {
        // Executa as ações sequencialmente para evitar conflitos de concorrência
        for (const id of ids) {
            try {
                const res = await fetch(`${window.API_URL}?action=${action}&id=${id}`)
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
            showToast(`${successes} aluno(s) atualizado(s) com sucesso.`, 'success');
        }
        if (failures > 0) {
            showToast(`${failures} aluno(s) falharam ao atualizar.`, 'error');
        }

        window.knownStudentsCache = null; // Limpa o cache
        loadStudents(); // Recarrega a tabela de alunos
    } catch (err) {
        showToast("Erro de comunicação ao processar ação em lote.", 'error');
    }
};

