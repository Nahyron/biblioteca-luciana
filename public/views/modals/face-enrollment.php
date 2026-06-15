<!-- MODAL DE CAPTURA FACIAL (Wizard 3 Etapas)
     Guia o usuário para capturar fotos de frente, lateral esquerda e lateral direita.
     Desenha os 68 pontos faciais (landmarks) em tempo real sobre o rosto.
-->
<div id="modal-face-enrollment" class="modal-overlay">
    <div class="modal-content enrollment-modal">
        <div class="enrollment-header">
            <h2><i class="fas fa-head-side-virus"></i> Captura Biométrica</h2>
            <p class="enrollment-subtitle">Capture 3 ângulos do rosto para maior precisão</p>
        </div>

        <div class="enrollment-body-grid">
            <!-- Coluna da Esquerda: Câmera e Captura -->
            <div class="enrollment-col-left">
                <!-- Capture Area -->
                <div class="enrollment-capture-area">
                    <!-- Instruction overlay -->
                    <div id="enrollment-instruction" class="enrollment-instruction">
                        <i class="fas fa-user instruction-icon"></i>
                        <span class="instruction-text">Olhe diretamente para a câmera</span>
                    </div>

                    <!-- Video + Canvas stack -->
                    <div class="enrollment-video-wrapper">
                        <div class="enrollment-camera-container">
                            <video id="enrollment-video" autoplay muted playsinline></video>
                            <canvas id="enrollment-canvas"></canvas>
                            <div id="enrollment-face-guide" class="face-guide">
                                <div class="face-oval"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Capture controls -->
                    <div class="enrollment-controls">
                        <div id="enrollment-quality" class="quality-indicator">
                            <i class="fas fa-circle-notch fa-spin"></i> Detectando rosto...
                        </div>
                        <button id="btn-capture-enrollment" class="btn-capture" disabled onclick="captureEnrollmentPhoto()">
                            <i class="fas fa-camera"></i> Capturar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Coluna da Direita: Progresso, Previews e Ações -->
            <div class="enrollment-col-right">
                <!-- Progress Bar -->
                <div class="enrollment-progress">
                    <div class="progress-step active" data-step="1">
                        <div class="step-circle">1</div>
                        <span>Frontal</span>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step" data-step="2">
                        <div class="step-circle">2</div>
                        <span>Esquerda</span>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step" data-step="3">
                        <div class="step-circle">3</div>
                        <span>Direita</span>
                    </div>
                </div>

                <!-- Preview Grid -->
                <div class="enrollment-previews">
                    <div class="preview-slot" id="preview-slot-1">
                        <div class="preview-placeholder"><i class="fas fa-user"></i></div>
                        <span>Frontal</span>
                    </div>
                    <div class="preview-slot" id="preview-slot-2">
                        <div class="preview-placeholder"><i class="fas fa-user" style="transform: scaleX(-1) rotate(-20deg);"></i></div>
                        <span>Esquerda</span>
                    </div>
                    <div class="preview-slot" id="preview-slot-3">
                        <div class="preview-placeholder"><i class="fas fa-user" style="transform: rotate(20deg);"></i></div>
                        <span>Direita</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="enrollment-actions">
                    <button id="btn-finish-enrollment" class="btn-primary" disabled onclick="finalizeEnrollment()">
                        <i class="fas fa-check-double"></i> Confirmar Biometria
                    </button>
                    <button class="btn-secondary" onclick="closeFaceEnrollment()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


