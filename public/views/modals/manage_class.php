<!-- MODAL DE GERENCIAMENTO DE ALUNOS NA TURMA -->
<div id="modal-manage-class" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <h2 id="manage-class-title">Gerenciar Turma</h2>

        <div style="margin-bottom: 1rem;">
            <h4>Adicionar Aluno Existente</h4>
            <div style="display: flex; gap: 10px;">
                <select id="select-add-student"
                    style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
                    <option value="" disabled selected>Selecione um aluno...</option>
                    <!-- Options carregadas via JS -->
                </select>
                <button class="btn-primary" onclick="addStudentToClass()">Adicionar</button>
            </div>
        </div>

        <h4>Alunos nesta turma</h4>
        <div class="table-container" style="max-height: 250px; overflow-y: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="class-students-body">
                    <!-- Popula via JS -->
                </tbody>
            </table>
        </div>

        <div
            style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.5rem; border-top: 1px solid #eee; padding-top: 1rem;">
            <button type="button" class="btn-secondary" onclick="closeManageClassModal()">Fechar</button>
            <button type="button" class="btn-action btn-delete" onclick="deleteCurrentClass()">EXCLUIR TURMA
                INTEIRA</button>
        </div>
    </div>
</div>
