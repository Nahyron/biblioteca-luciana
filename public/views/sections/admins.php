<!-- SEÇÃO: GERENCIAMENTO DE ADMINISTRADORES E PROFESSORES -->
<section id="sec-admins">

    <!-- ════════════════════════════════════════
         NAVEGAÇÃO POR ABAS
    ════════════════════════════════════════ -->
    <div style="display: flex; gap: 0; margin-bottom: 2rem; border-bottom: 2px solid #eee; position: relative;">
        <button class="admin-tab-btn" data-tipo="admin" onclick="switchAdminTab('admin')"
            style="padding: 0.8rem 1.75rem; font-size: 0.95rem; font-weight: 800; border: none;
                   background: none; cursor: pointer; color: var(--primary, #BC0000);
                   border-bottom: 3px solid var(--primary, #BC0000); margin-bottom: -2px;
                   display: flex; align-items: center; gap: 0.6rem; transition: all 0.2s ease;">
            <i class="fas fa-user-shield"></i> Administradores
        </button>
        <button class="admin-tab-btn" data-tipo="professor" onclick="switchAdminTab('professor')"
            style="padding: 0.8rem 1.75rem; font-size: 0.95rem; font-weight: 600; border: none;
                   background: none; cursor: pointer; color: #999;
                   border-bottom: 3px solid transparent; margin-bottom: -2px;
                   display: flex; align-items: center; gap: 0.6rem; transition: all 0.2s ease;">
            <i class="fas fa-chalkboard-teacher"></i> Professores
        </button>
    </div>

    <!-- ════════════════════════════════════════
         PAINEL: ADMINISTRADORES
    ════════════════════════════════════════ -->
    <div class="admin-tab-panel" data-tipo="admin">

        <!-- Card: Cadastrar Novo Administrador -->
        <?php if (\App\Infrastructure\Auth\SessionAuth::getAdminTipo() === 'admin'): ?>
        <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--primary, #BC0000);">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(188,0,0,0.08);
                            display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-shield" style="color: var(--primary, #BC0000); font-size: 1.1rem;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.05rem;">Cadastrar Novo Administrador</h3>
                    <p style="margin: 0; font-size: 0.82rem; color: #888;">Acesso total ao painel de controle</p>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 180px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #666;
                                  text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.4rem;">
                        <i class="fas fa-user" style="margin-right: 4px;"></i> Usuário
                    </label>
                    <input type="text" id="admin-usuario-admin" placeholder="Ex: admin.silva"
                        autocomplete="off"
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px;
                               font-size: 0.95rem; box-sizing: border-box; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='var(--primary, #BC0000)'"
                        onblur="this.style.borderColor='#ddd'">
                </div>
                <div style="flex: 1; min-width: 180px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #666;
                                  text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.4rem;">
                        <i class="fas fa-lock" style="margin-right: 4px;"></i> Senha
                    </label>
                    <input type="password" id="admin-senha-admin" placeholder="Mínimo 4 caracteres"
                        autocomplete="new-password"
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px;
                               font-size: 0.95rem; box-sizing: border-box; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='var(--primary, #BC0000)'"
                        onblur="this.style.borderColor='#ddd'"
                        onkeypress="if(event.key==='Enter') createAdmin('admin')">
                </div>
                <div style="flex-shrink: 0;">
                    <button id="admin-btn-create-admin" class="btn-primary" onclick="createAdmin('admin')"
                        style="padding: 0.75rem 1.5rem; display: flex; align-items: center;
                               gap: 0.5rem; white-space: nowrap; border-radius: 8px;">
                        <i class="fas fa-plus"></i> Cadastrar Admin
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Card: Lista de Administradores -->
        <div class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fas fa-list" style="color: #999; font-size: 0.9rem;"></i>
                    Lista de Administradores
                </h3>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <?php if (\App\Infrastructure\Auth\SessionAuth::getAdminTipo() === 'admin'): ?>
                    <button id="btn-delete-selected-admin" class="btn-danger" onclick="deleteSelectedAdmins('admin')"
                        style="padding: 0.4rem 0.8rem; border: none; border-radius: 6px; background: #dc3545;
                               cursor: pointer; font-size: 0.82rem; color: #fff; display: none; align-items: center; gap: 5px; font-weight: bold;">
                        <i class="fas fa-trash-alt"></i> Excluir Selecionados
                    </button>
                    <?php endif; ?>
                    <button onclick="loadAdmins('admin')"
                        style="padding: 0.4rem 0.8rem; border: 1px solid #ddd; border-radius: 6px; background: #fff;
                               cursor: pointer; font-size: 0.82rem; color: #666; display: flex; align-items: center; gap: 5px;"
                        title="Atualizar lista">
                        <i class="fas fa-sync-alt"></i> Atualizar
                    </button>
                </div>
            </div>
            <div class="table-container">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #fafafa; border-bottom: 2px solid #f0f0f0;">
                            <?php if (\App\Infrastructure\Auth\SessionAuth::getAdminTipo() === 'admin'): ?>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: #888;
                                       text-transform: uppercase; letter-spacing: 0.5px; width: 40px; text-align: center;">
                                <input type="checkbox" id="select-all-admin" onclick="toggleAllAdmins(this, 'admin')">
                            </th>
                            <?php endif; ?>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: #888;
                                       text-transform: uppercase; letter-spacing: 0.5px; width: 60px;">ID</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: #888;
                                       text-transform: uppercase; letter-spacing: 0.5px;">Usuário</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: #888;
                                       text-transform: uppercase; letter-spacing: 0.5px; width: 130px;">Criado em</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: #888;
                                       text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="admins-table-body-admin">
                        <tr>
                            <td colspan="5" style="text-align:center; padding:2.5rem; color:#bbb;">
                                <i class="fas fa-spinner fa-spin"></i> Carregando...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         PAINEL: PROFESSORES
    ════════════════════════════════════════ -->
    <div class="admin-tab-panel" data-tipo="professor" style="display: none;">

        <!-- Card: Cadastrar Novo Professor -->
        <?php if (\App\Infrastructure\Auth\SessionAuth::getAdminTipo() === 'admin'): ?>
        <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid #2563eb;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(37,99,235,0.08);
                            display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-chalkboard-teacher" style="color: #2563eb; font-size: 1.1rem;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.05rem;">Cadastrar Novo Professor</h3>
                    <p style="margin: 0; font-size: 0.82rem; color: #888;">Acesso de professor ao sistema</p>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 180px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #666;
                                  text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.4rem;">
                        <i class="fas fa-user" style="margin-right: 4px;"></i> Usuário
                    </label>
                    <input type="text" id="admin-usuario-professor" placeholder="Ex: prof.santos"
                        autocomplete="off"
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px;
                               font-size: 0.95rem; box-sizing: border-box; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='#2563eb'"
                        onblur="this.style.borderColor='#ddd'">
                </div>
                <div style="flex: 1; min-width: 180px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #666;
                                  text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.4rem;">
                        <i class="fas fa-lock" style="margin-right: 4px;"></i> Senha
                    </label>
                    <input type="password" id="admin-senha-professor" placeholder="Mínimo 4 caracteres"
                        autocomplete="new-password"
                        style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px;
                               font-size: 0.95rem; box-sizing: border-box; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='#2563eb'"
                        onblur="this.style.borderColor='#ddd'"
                        onkeypress="if(event.key==='Enter') createAdmin('professor')">
                </div>
                <div style="flex-shrink: 0; display: flex; gap: 8px;">
                    <button id="admin-btn-create-professor" class="btn-primary" onclick="createAdmin('professor')"
                        style="padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem;
                               white-space: nowrap; border-radius: 8px; background: #2563eb; border-color: #2563eb;">
                        <i class="fas fa-plus"></i> Cadastrar Professor
                    </button>
                    <button class="btn-primary" onclick="triggerTeacherExcelImport()"
                        style="padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem;
                               white-space: nowrap; border-radius: 8px; background: #2ec4b6; border-color: #2ec4b6;">
                        <i class="fas fa-file-excel"></i> Importar Planilha
                    </button>
                    <input type="file" id="teacher-excel-import-file" accept=".xlsx, .xls, .csv" style="display: none;" onchange="handleTeacherExcelImport(event)">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Card: Lista de Professores -->
        <div class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fas fa-list" style="color: #999; font-size: 0.9rem;"></i>
                    Lista de Professores
                </h3>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <?php if (\App\Infrastructure\Auth\SessionAuth::getAdminTipo() === 'admin'): ?>
                    <button id="btn-delete-selected-professor" class="btn-danger" onclick="deleteSelectedAdmins('professor')"
                        style="padding: 0.4rem 0.8rem; border: none; border-radius: 6px; background: #dc3545;
                               cursor: pointer; font-size: 0.82rem; color: #fff; display: none; align-items: center; gap: 5px; font-weight: bold;">
                        <i class="fas fa-trash-alt"></i> Excluir Selecionados
                    </button>
                    <?php endif; ?>
                    <button onclick="loadAdmins('professor')"
                        style="padding: 0.4rem 0.8rem; border: 1px solid #ddd; border-radius: 6px; background: #fff;
                               cursor: pointer; font-size: 0.82rem; color: #666; display: flex; align-items: center; gap: 5px;"
                        title="Atualizar lista">
                        <i class="fas fa-sync-alt"></i> Atualizar
                    </button>
                </div>
            </div>
            <div class="table-container">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #fafafa; border-bottom: 2px solid #f0f0f0;">
                            <?php if (\App\Infrastructure\Auth\SessionAuth::getAdminTipo() === 'admin'): ?>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: #888;
                                       text-transform: uppercase; letter-spacing: 0.5px; width: 40px; text-align: center;">
                                <input type="checkbox" id="select-all-professor" onclick="toggleAllAdmins(this, 'professor')">
                            </th>
                            <?php endif; ?>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: #888;
                                       text-transform: uppercase; letter-spacing: 0.5px; width: 60px;">ID</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: #888;
                                       text-transform: uppercase; letter-spacing: 0.5px;">Usuário</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: #888;
                                       text-transform: uppercase; letter-spacing: 0.5px; width: 130px;">Criado em</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: #888;
                                       text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="admins-table-body-professor">
                        <tr>
                            <td colspan="5" style="text-align:center; padding:2.5rem; color:#bbb;">
                                <i class="fas fa-spinner fa-spin"></i> Carregando...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</section>
