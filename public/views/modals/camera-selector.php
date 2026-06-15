<!-- MODAL DE SELEÇÃO DE CÂMERA COMPARTILHADO -->
<div id="modal-camera-selector" class="modal-overlay" style="z-index: 1005;">
    <div class="modal-content">
        <h2><i class="fas fa-camera"></i> Selecionar Câmera</h2>
        <p style="margin-top: -1.5rem; margin-bottom: 1.5rem; color: #a0aec0; font-size: 0.95rem;">
            Selecione o dispositivo de vídeo que deseja utilizar para o reconhecimento ou captura.
        </p>
        
        <div class="form-group">
            <label for="camera-device-select">Câmeras Disponíveis</label>
            <select id="camera-device-select" class="form-input" required>
                <!-- Populados via JS -->
            </select>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
            <button id="btn-confirm-camera" class="btn-primary" style="flex: 2;">Confirmar Câmera</button>
            <button type="button" class="btn-secondary" style="flex: 1;" onclick="closeCameraSelector()">Cancelar</button>
        </div>
    </div>
</div>
