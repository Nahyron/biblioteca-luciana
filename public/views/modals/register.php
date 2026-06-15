<!-- MODAL DE CADASTRO (Pop-up)
     Aparece quando a IA detecta um rosto novo ou quando o botão Novo Registro é clicado.
-->
<div id="modal-register" class="modal-overlay">
    <div class="modal-content">
        <h2>Captura de Biometria</h2>
        <form id="registration-form" onsubmit="event.preventDefault(); saveRegistration();">
            <!-- Descriptor: Campo oculto que guarda o array de números da face (média dos 3 ângulos) -->
            <input type="hidden" id="reg-descriptor">
            <!-- Face Data: Campo oculto com dados completos (descriptors + landmarks de 3 ângulos) -->
            <input type="hidden" id="reg-face-data">

            <div class="form-group">
                <label for="reg-name">Nome Completo</label>
                <input type="text" id="reg-name" placeholder="Ex: Nome do Aluno" required>
            </div>

            <div class="form-group">
                <label for="reg-matricula">Turma Responsável</label>
                <select id="reg-matricula" required>
                    <option value="" disabled selected>Selecione a Turma</option>
                </select>
            </div>

            <div id="face-capture-status" style="margin-bottom: 1.5rem; font-weight: bold; text-align: center; color: var(--error);">
                [Biometria Padrão (Sem Câmera)]
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-primary" style="flex: 2;">Confirmar Cadastro</button>
                <button type="button" class="btn-secondary" style="flex: 1;"
                    onclick="closeRegistration()">Sair</button>
            </div>
        </form>
    </div>
</div>
