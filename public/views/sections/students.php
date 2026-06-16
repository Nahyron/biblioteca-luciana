<!-- SEÇÃO 3: GESTÃO DE ALUNOS
     Tabela para visualização de cadastros e exclusão de usuários ruins/antigos.
-->
<section id="sec-students">
    <div class="card">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <h3 style="margin-bottom: 0;">Banco de Dados de Usuários</h3>
                <button id="toggle-inactive-btn" class="btn-secondary" onclick="toggleInactiveMode()" style="padding: 0.5rem 1rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; border: 1px solid #6C757D; color: #6C757D; background: transparent; height: fit-content; border-radius: 8px; font-weight: 700; cursor: pointer; transition: var(--transition);">
                    <i class="fas fa-user-slash"></i> Ver Alunos Inativos
                </button>
                <button id="btn-delete-selected-students" class="btn-danger" onclick="deleteSelectedStudents()" style="padding: 0.5rem 1rem; font-size: 0.85rem; display: none; align-items: center; gap: 0.5rem; background-color: #dc3545; color: white; border: none; height: fit-content; border-radius: 8px; font-weight: 700; cursor: pointer; transition: var(--transition);">
                    <i class="fas fa-trash-alt"></i> Desativar Selecionados
                </button>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="search-student-name" onkeyup="filterStudents()"
                    placeholder="Pesquisar nome..." class="form-control"
                    style="padding: 0.6rem; border: 1px solid #ddd; border-radius: 8px; min-width: 200px;">
                <input type="text" id="search-student-class" onkeyup="filterStudents()"
                    placeholder="Pesquisar turma..." class="form-control"
                    style="padding: 0.6rem; border: 1px solid #ddd; border-radius: 8px; min-width: 150px;">
                <button class="btn-secondary" onclick="exportStudentsXLS()" style="display: flex; align-items: center; gap: 0.4rem; color: #1d6f42; border-color: #1d6f42;">
                    <i class="fas fa-file-excel"></i> Exportar XLS
                </button>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all-students" onclick="toggleAllStudents(this)"></th>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Matrícula/Turma</th>
                        <th>Última Presença</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="student-table-body">
                    <!-- Popula via AJAX (app.js) -->
                </tbody>
            </table>
        </div>
    </div>
</section>
