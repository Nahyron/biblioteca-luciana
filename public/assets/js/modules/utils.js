/**
 * Utilitários Compartilhados (Relógio, Toasts)
 */

function updateClock() {
    const clock = document.getElementById('clock');
    if (clock) {
        clock.innerText = new Date().toLocaleTimeString('pt-BR');
    }
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerText = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

/**
 * Substitui o window.confirm nativo por um Modal Customizado.
 * Retorna uma Promise que resolve para true se o usuário aceitar.
 */
window.openConfirmModal = function(title, message, yesBtnText = 'Sim, Excluir') {
    return new Promise((resolve) => {
        const modal = document.getElementById('modal-confirm');
        document.getElementById('confirm-title').innerText = title;
        document.getElementById('confirm-message').innerText = message;
        
        const btnYes = document.getElementById('confirm-btn-yes');
        btnYes.innerText = yesBtnText;
        
        // Remove listeners antigos
        const newBtnYes = btnYes.cloneNode(true);
        btnYes.parentNode.replaceChild(newBtnYes, btnYes);

        newBtnYes.onclick = () => {
            window.currentConfirmReject = null; // Limpa o reject antes de fechar
            closeConfirmModal();
            resolve(true);
        };

        window.currentConfirmReject = () => resolve(false);

        modal.classList.add('active');
    });
}

window.closeConfirmModal = function() {
    document.getElementById('modal-confirm').classList.remove('active');
    if(window.currentConfirmReject) {
        window.currentConfirmReject();
        window.currentConfirmReject = null;
    }
}

/**
 * Substitui o window.prompt nativo por um Modal Customizado.
 * Retorna uma Promise com o valor digitado ou null se cancelado.
 */
window.openEditModal = function(title, message, initialValue = '') {
    return new Promise((resolve) => {
        const modal = document.getElementById('modal-edit');
        document.getElementById('edit-title').innerText = title;
        document.getElementById('edit-message').innerText = message;
        
        const input = document.getElementById('edit-input-value');
        input.value = initialValue;
        
        const btnSave = document.getElementById('edit-btn-save');
        
        // Remove listeners antigos
        const newBtnSave = btnSave.cloneNode(true);
        btnSave.parentNode.replaceChild(newBtnSave, btnSave);

        newBtnSave.onclick = () => {
            window.currentEditReject = null; // Limpa o reject antes de fechar
            closeEditModal();
            resolve(input.value);
        };

        input.onkeypress = (e) => {
            if (e.key === 'Enter') newBtnSave.click();
        }

        window.currentEditReject = () => resolve(null);

        modal.classList.add('active');
        setTimeout(() => input.focus(), 100);
    });
}

window.closeEditModal = function() {
    document.getElementById('modal-edit').classList.remove('active');
    if(window.currentEditReject) {
        window.currentEditReject();
        window.currentEditReject = null;
    }
}

/**
 * Converte de forma segura uma string de data vinda do MySQL (YYYY-MM-DD HH:MM:SS)
 * para um objeto Date, compatível com todos os navegadores.
 */
window.safeParseDate = function(dateStr) {
    if (!dateStr) return null;
    // Substitui espaço por T para formar o formato ISO 8601 (YYYY-MM-DDTHH:MM:SS)
    const isoStr = String(dateStr).replace(' ', 'T');
    const date = new Date(isoStr);
    return isNaN(date.getTime()) ? null : date;
}

/**
 * Formata de forma segura data e hora (toLocaleString)
 */
window.safeFormatLocaleString = function(dateStr, locales = 'pt-BR', options = {}) {
    const date = window.safeParseDate(dateStr);
    if (!date) return dateStr || '';
    return date.toLocaleString(locales, options);
}

/**
 * Formata de forma segura apenas a hora (toLocaleTimeString).
 * Se for hoje, exibe a hora. Se for outro dia, exibe "DD/MM às HH:MM".
 */
window.safeFormatLocaleTimeString = function(dateStr, locales = 'pt-BR', options = { hour: '2-digit', minute: '2-digit' }) {
    const date = window.safeParseDate(dateStr);
    if (!date) return 'Ausente';
    
    const today = new Date();
    const isToday = date.getDate() === today.getDate() &&
                    date.getMonth() === today.getMonth() &&
                    date.getFullYear() === today.getFullYear();
                    
    if (isToday) {
        return date.toLocaleTimeString(locales, options);
    } else {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}/${month} às ${hours}:${minutes}`;
    }
}

/**
 * Higieniza o input para Nome de Aluno ou Nome de Turma.
 * Permite apenas letras (incluindo acentuadas), números, espaços, hífens e caracteres ordinais (º, ª).
 */
window.sanitizeNameInput = function(value) {
    if (!value) return '';
    return value.replace(/[^A-Za-z0-9À-ÖØ-öø-ÿ\s\-ºª]/g, '');
}

/**
 * Higieniza o input para Nome de Usuário (login).
 * Permite apenas letras, números, pontos (.), underlines (_), hífens (-) e arrobas (@).
 */
window.sanitizeUsernameInput = function(value) {
    if (!value) return '';
    return value.replace(/[^A-Za-z0-9\._\-@]/g, '');
}

/**
 * Configura um listener de input em tempo real para impedir caracteres indesejados.
 * @param {HTMLInputElement|string} elementOrSelector - Elemento ou seletor CSS do input.
 * @param {string} type - 'name' ou 'username'.
 */
window.setupInputSanitizer = function(elementOrSelector, type) {
    const input = typeof elementOrSelector === 'string' 
        ? document.querySelector(elementOrSelector) 
        : elementOrSelector;
        
    if (!input) return;

    let lastToastTime = 0;
    const toastCooldown = 3000; // 3 segundos de cooldown para não encher a tela de Toasts

    input.addEventListener('input', function(e) {
        const originalVal = e.target.value;
        let sanitizedVal = '';
        let forbiddenCharDetected = false;

        if (type === 'name') {
            sanitizedVal = window.sanitizeNameInput(originalVal);
            if (originalVal.match(/[^A-Za-z0-9À-ÖØ-öø-ÿ\s\-ºª]/)) {
                forbiddenCharDetected = true;
            }
        } else if (type === 'username') {
            sanitizedVal = window.sanitizeUsernameInput(originalVal);
            if (originalVal.match(/[^A-Za-z0-9\._\-@]/)) {
                forbiddenCharDetected = true;
            }
        }

        if (forbiddenCharDetected) {
            e.target.value = sanitizedVal;
            
            // Exibe aviso amigável se passou o tempo de cooldown
            const now = Date.now();
            if (now - lastToastTime > toastCooldown) {
                if (typeof showToast === 'function') {
                    showToast("Caracteres especiais não são permitidos neste campo.", "error");
                }
                lastToastTime = now;
            }
        }
    });
}

// Inicializa a higienização de inputs para o modal universal de edição
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.setupInputSanitizer === 'function') {
        window.setupInputSanitizer('#edit-input-value', 'name');
    }
});



