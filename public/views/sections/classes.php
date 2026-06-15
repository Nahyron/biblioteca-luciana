<!-- SEÇÃO: GESTÃO DE TURMAS -->
<section id="sec-classes">
    <!-- Visualização 1: Grid de Turmas -->
    <div class="card" id="classes-list-view">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <h3 style="margin:0;">Gestão de Turmas</h3>
            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <!-- Barra de Pesquisa -->
                <div style="position: relative;">
                    <i class="fas fa-search" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:#aaa;pointer-events:none;"></i>
                    <input type="text" id="classes-search" oninput="searchClasses()" placeholder="Buscar turma..." style="padding: 0.5rem 1rem 0.5rem 2.2rem; border: 1px solid var(--gray-border, #ddd); border-radius: 8px; font-size: 0.9rem; width: 200px;">
                </div>
                <button class="btn-primary" onclick="openClassModal()">+ Nova Turma</button>
            </div>
        </div>
        <div id="classes-grid"
            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
            <!-- Cards de turmas injetados via AJAX (app.js) -->
        </div>
    </div>

    <!-- Visualização 2: Tabela de Alunos da Turma Selecionada -->
    <div class="card" id="class-detail-view" style="display: none;">
        <!-- Cabeçalho com Botão Voltar -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="btn-secondary" onclick="backToClassesGrid()" style="padding: 0.5rem 1rem; display: flex; align-items: center; gap: 6px; font-size: 0.9rem; border-radius: 8px; border: 1px solid #ddd; background: #fff; cursor: pointer;">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
                <h3 style="margin:0;" id="class-detail-title">Alunos da Turma</h3>
            </div>
            
            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <!-- Botão de Adicionar Aluno Manualmente -->
                <button class="btn-primary" onclick="addNewStudentToCurrentClass()" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-user-plus"></i> Novo Aluno
                </button>

                <!-- Botão de Importação por Excel -->
                <button class="btn-primary" onclick="triggerExcelImport()" style="display: flex; align-items: center; gap: 6px; background-color: #2ec4b6; border-color: #2ec4b6;">
                    <i class="fas fa-file-excel"></i> Importar Alunos (Excel)
                </button>
                <input type="file" id="excel-import-file" accept=".xlsx, .xls, .csv" style="display: none;" onchange="handleExcelImport(event)">
                
                <!-- Excluir Turma Inteira -->
                <button class="btn-action btn-delete" onclick="deleteCurrentClassFromDetail()" style="padding: 0.6rem 1rem; font-size: 0.85rem;">
                    <i class="fas fa-trash"></i> Excluir Turma
                </button>
            </div>
        </div>

        <!-- Área de Filtros e Busca -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; background: rgba(0,0,0,0.02); padding: 1rem; border-radius: 8px; border: 1px solid #eee;">
            <!-- Busca por nome -->
            <div style="position: relative; flex: 1; min-width: 250px;">
                <i class="fas fa-search" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:#aaa;pointer-events:none;"></i>
                <input type="text" id="class-student-search" oninput="filterClassStudents()" placeholder="Buscar aluno pelo nome..." style="width: 100%; padding: 0.55rem 1rem 0.55rem 2.2rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box;">
            </div>

            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <!-- Filtro Facial (Cadastrado ou Não) -->
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.85rem; color: #666; font-weight: 500;">Biometria:</span>
                    <select id="class-student-facial-filter" onchange="filterClassStudents()" style="padding: 0.55rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem; outline: none; background: #fff; cursor: pointer;">
                        <option value="all">Todos</option>
                        <option value="yes">Cadastrados</option>
                        <option value="no">Não Cadastrados</option>
                    </select>
                </div>

                <!-- Adicionar Aluno Existente (Vincular) -->
                <div style="display: flex; align-items: center; gap: 0.5rem; border-left: 1px solid #eee; padding-left: 1rem;">
                    <select id="select-add-student-detail" style="padding: 0.55rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem; max-width: 200px; outline: none; background: #fff;">
                        <option value="" disabled selected>Vincular aluno...</option>
                    </select>
                    <button class="btn-primary" onclick="addStudentToClassFromDetail()" style="padding: 0.55rem 1rem;">Adicionar</button>
                </div>
            </div>
        </div>

        <!-- Tabela de Alunos -->
        <div class="table-container" style="margin-top: 1rem; border: 1px solid #eee; border-radius: 8px; overflow: hidden; background: #fff;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #fcfcfc; border-bottom: 1px solid #eee;">
                        <th style="width: 40%; padding: 12px 16px; font-weight: 600; color: #444; font-size: 0.9rem;">Nome</th>
                        <th style="width: 30%; padding: 12px 16px; font-weight: 600; color: #444; font-size: 0.9rem; text-align: center;">Status Facial</th>
                        <th style="width: 30%; padding: 12px 16px; font-weight: 600; color: #444; font-size: 0.9rem; text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody id="class-students-detail-body">
                    <!-- Preenchido dinamicamente via JS -->
                </tbody>
            </table>
        </div>
    </div>
</section>

