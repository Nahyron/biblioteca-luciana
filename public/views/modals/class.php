<!-- MODAL DE NOVA TURMA -->
<div id="modal-class" class="modal-overlay">
    <div class="modal-content">
        <h2 id="class-modal-title">Nova Turma</h2>
        <form id="class-form" onsubmit="event.preventDefault(); saveClass();">
            <input type="hidden" id="class-id">
            <div class="form-group">
                <label for="class-name">Nome da Turma</label>
                <input type="text" id="class-name" placeholder="Ex: TDS26" required>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                <button type="submit" class="btn-primary" style="flex: 2;">Salvar</button>
                <button type="button" class="btn-secondary" style="flex: 1;"
                    onclick="closeClassModal()">Sair</button>
            </div>
        </form>
    </div>
</div>
