<!-- Container flutuante para alertas rápidos (Toasts) -->
<div id="toast-container"></div>

<!-- Modal Universal de Confirmação (Excluir) -->
<div id="modal-confirm" class="modal-overlay" style="z-index: 9999;">
    <div class="modal-content" style="max-width: 400px; text-align: center;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--error); margin-bottom: 1rem;"></i>
        <h3 id="confirm-title" style="margin-bottom: 1rem;">Confirmar Exclusão</h3>
        <p id="confirm-message" style="color: #666; margin-bottom: 1.5rem;"></p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn-secondary" onclick="closeConfirmModal()">Cancelar</button>
            <button class="btn-action btn-delete" id="confirm-btn-yes">Sim, Excluir</button>
        </div>
    </div>
</div>

<!-- Modal Universal de Edição (Input) -->
<div id="modal-edit" class="modal-overlay" style="z-index: 9999;">
    <div class="modal-content" style="max-width: 400px;">
        <h3 id="edit-title" style="margin-bottom: 1rem;">Editar Registro</h3>
        <p id="edit-message" style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;"></p>
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <input type="text" id="edit-input-value" class="form-control" autocomplete="off" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn-secondary" onclick="closeEditModal()">Cancelar</button>
            <button class="btn-primary" id="edit-btn-save">Salvar</button>
        </div>
    </div>
</div>

<!-- Modal de Sucesso no Reconhecimento Facial (Autoatendimento) -->
<div id="modal-recognition-success" class="modal-overlay modal-success-overlay" style="z-index: 10000;">
    <div class="modal-content success-card animate-pop-in" style="max-width: 420px;">
        <div class="success-icon-wrapper">
            <i class="fas fa-check-circle animate-scale-up"></i>
        </div>
        <h2 id="success-student-name">Nome do Aluno</h2>
        <p>Reconhecimento Facial Concluído!</p>
        <div class="success-status-tag">Acesso Liberado</div>
    </div>
</div>

<!-- Modal de Alerta: Aluno Inativo Detectado (Global SSE) -->
<div id="modal-inactive-alert" class="modal-overlay modal-inactive-overlay" style="z-index: 10001;">
    <div class="modal-content inactive-alert-card animate-pop-in" style="max-width: 420px;">
        <div class="inactive-icon-wrapper">
            <i class="fas fa-ban animate-scale-up"></i>
        </div>
        <h2 id="inactive-alert-name">Nome do Aluno</h2>
        <p>Este aluno está <strong>inativo</strong> no sistema.</p>
        <div class="inactive-status-tag">Acesso Negado</div>
    </div>
</div>

