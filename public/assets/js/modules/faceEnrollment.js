/**
 * Módulo de Enrollment Facial (Wizard 3 Ângulos)
 * Gerencia a captura guiada de 3 fotos com visualização de landmarks em tempo real.
 */

// --- State ---
const enrollmentState = {
    currentStep: 1,
    totalSteps: 3,
    captures: [],          // Array of {descriptor, landmarks, imageData}
    stream: null,
    detectionLoop: null,
    isProcessing: false
};

// Variable control for enrolling biometrics for an existing student directly
let enrollmentTargetStudentId = null;

const STEP_CONFIG = [
    { step: 1, icon: 'fa-user', text: 'Olhe diretamente para a câmera', label: 'Frontal' },
    { step: 2, icon: 'fa-arrow-left', text: 'Vire levemente o rosto para a esquerda', label: 'Esquerda' },
    { step: 3, icon: 'fa-arrow-right', text: 'Vire levemente o rosto para a direita', label: 'Direita' }
];

// Landmark region colors
const LANDMARK_COLORS = {
    jaw: 'rgba(255, 255, 255, 0.5)',   // White - jawline (0-16)
    eyebrowL: 'rgba(0, 212, 255, 0.8)',     // Cyan - left eyebrow (17-21)
    eyebrowR: 'rgba(0, 212, 255, 0.8)',     // Cyan - right eyebrow (22-26)
    nose: 'rgba(0, 255, 136, 0.8)',     // Green - nose (27-35)
    eyeL: 'rgba(0, 170, 255, 0.9)',     // Blue - left eye (36-41)
    eyeR: 'rgba(0, 170, 255, 0.9)',     // Blue - right eye (42-47)
    mouthOuter: 'rgba(255, 68, 102, 0.8)',    // Red - outer mouth (48-59)
    mouthInner: 'rgba(255, 100, 130, 0.7)'    // Light red - inner mouth (60-67)
};

/**
 * Opens the face enrollment wizard modal and starts the camera.
 */
window.openFaceEnrollment = async function () {
    resetEnrollmentState();

    // Opcionalmente, pode fechar formulários abertos:
    const regModal = document.getElementById('modal-register');
    if (regModal) regModal.classList.remove('active');

    // Garante que qualquer resquício pare
    if (enrollmentState.stream) {
        stopEnrollmentCamera();
    }

    // Pergunta qual câmera usar antes de abrir o wizard
    window.showCameraSelector(async () => {
        // Abre o Modal do Wizard!
        const faceModal = document.getElementById('modal-face-enrollment');
        if (faceModal) faceModal.classList.add('active');

        await new Promise(resolve => setTimeout(resolve, 300));
        await startEnrollmentCamera();
    }, () => {
        console.log("Seleção de câmera cancelada pelo usuário.");
    });
};

/**
 * Closes the enrollment wizard and cleans up.
 */
window.closeFaceEnrollment = function () {
    stopEnrollmentCamera();

    const faceModal = document.getElementById('modal-face-enrollment');
    if (faceModal) faceModal.classList.remove('active');

    resetEnrollmentState();

    // Re-liga o hardware principal
    if (typeof window.startVideo === 'function') {
        setTimeout(() => window.startVideo(), 500);
    }
};

/**
 * Starts the facial enrollment wizard directly for an existing student.
 */
window.startFacialEnrollmentForStudent = async function (studentId, studentName) {
    resetEnrollmentState();

    enrollmentTargetStudentId = studentId;

    // Customize the subtitle of the Wizard to show who is being enrolled
    const subtitleEl = document.querySelector('.enrollment-subtitle');
    if (subtitleEl) {
        subtitleEl.innerHTML = `Cadastrando biometria facial para: <strong>${studentName}</strong>`;
    }

    // Close registration form if active
    const regModal = document.getElementById('modal-register');
    if (regModal) regModal.classList.remove('active');

    // Stop existing stream if any
    if (enrollmentState.stream) {
        stopEnrollmentCamera();
    }

    // Pergunta qual câmera usar antes de abrir o wizard
    window.showCameraSelector(async () => {
        // Open Face Wizard modal
        const faceModal = document.getElementById('modal-face-enrollment');
        if (faceModal) faceModal.classList.add('active');

        await new Promise(resolve => setTimeout(resolve, 300));
        await startEnrollmentCamera();
    }, () => {
        console.log("Seleção de câmera cancelada pelo usuário.");
    });
};

/**
 * Resets the enrollment state for a fresh start.
 */
function resetEnrollmentState() {
    enrollmentState.currentStep = 1;
    enrollmentState.captures = [];
    enrollmentState.isProcessing = false;
    enrollmentTargetStudentId = null; // Reset student target ID

    // Reset subtitle to original instruction
    const subtitleEl = document.querySelector('.enrollment-subtitle');
    if (subtitleEl) {
        subtitleEl.textContent = 'Capture 3 ângulos do rosto para maior precisão';
    }

    // Reset UI
    document.querySelectorAll('.progress-step').forEach(el => {
        el.classList.remove('active', 'done');
    });
    document.querySelector('.progress-step[data-step="1"]')?.classList.add('active');
    document.querySelectorAll('.progress-line').forEach(el => el.classList.remove('done'));

    document.querySelectorAll('.preview-slot').forEach(el => {
        el.classList.remove('captured');
        const ph = el.querySelector('.preview-placeholder');
        const img = ph?.querySelector('img');
        if (img) img.remove();
    });

    updateStepInstruction(1);
    const btnFinish = document.getElementById('btn-finish-enrollment');
    if (btnFinish) btnFinish.disabled = true;
    const btnCapture = document.getElementById('btn-capture-enrollment');
    if (btnCapture) btnCapture.disabled = true;
}

/**
 * Starts the camera for the enrollment modal.
 */
async function startEnrollmentCamera() {
    const video = document.getElementById('enrollment-video');
    if (!video) return;

    try {
        const constraints = {
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        };

        if (window.selectedCameraDeviceId) {
            constraints.video.deviceId = { exact: window.selectedCameraDeviceId };
        } else {
            constraints.video.facingMode = 'user';
        }

        let stream;
        try {
            stream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch (err) {
            console.warn("Falha ao abrir câmera selecionada, tentando padrão...", err);
            // Fallback para qualquer câmera
            stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 } }
            });
        }

        video.srcObject = stream;
        enrollmentState.stream = stream;

        video.onloadedmetadata = () => {
            video.play();

            // Ao desvincular do Scanner principal, os modelos precisam ser chamados aqui
            if (!window.modelsLoaded && typeof loadModels === 'function') {
                updateQualityIndicator('Carregando modelos de IA...', false);
                loadModels();
            }

            startEnrollmentDetection();
        };
    } catch (err) {
        updateQualityIndicator('Câmera indisponível', false);
        if (typeof showToast === 'function') {
            showToast('Não foi possível acessar a câmera.', 'error');
        }
    }
}

/**
 * Stops the enrollment camera and detection loop.
 */
function stopEnrollmentCamera() {
    if (enrollmentState.detectionLoop) {
        clearInterval(enrollmentState.detectionLoop);
        enrollmentState.detectionLoop = null;
    }

    if (enrollmentState.stream) {
        enrollmentState.stream.getTracks().forEach(track => track.stop());
        enrollmentState.stream = null;
    }

    const video = document.getElementById('enrollment-video');
    if (video) {
        video.pause();
        video.srcObject = null;
    }

    const canvas = document.getElementById('enrollment-canvas');
    if (canvas) {
        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
    }
}

/**
 * Main detection loop: detects face + draws landmarks in real-time.
 */
function startEnrollmentDetection() {
    const video = document.getElementById('enrollment-video');
    const canvas = document.getElementById('enrollment-canvas');
    if (!video || !canvas) return;

    enrollmentState.detectionLoop = setInterval(async () => {
        if (!window.modelsLoaded) {
            updateQualityIndicator('Aguardando modelos de IA...', false);
            return; // Try again in the next tick
        }

        if (enrollmentState.isProcessing || video.paused || !enrollmentState.stream) return;

        const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
        if (displaySize.width === 0 || displaySize.height === 0) return;

        canvas.width = displaySize.width;
        canvas.height = displaySize.height;

        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        try {
            // Aumenta o inputSize para 320 e diminui o threshold para a IA captar narizes de lado (Side Profiles) e não dropar o tracking
            const detection = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.4 }))
                .withFaceLandmarks()
                .withFaceDescriptor();

            const guide = document.getElementById('enrollment-face-guide');

            if (detection) {
                const resized = faceapi.resizeResults(detection, displaySize);
                const box = resized.detection.box;

                // Usamos height ao invés de width pois larguras encolhem muito ao virar o rosto lateralmente!
                const faceRatio = Math.max(box.width / displaySize.width, box.height / displaySize.height);

                // Calcular centro do círculo/elipse delimitador e do rosto detectado
                const cx = displaySize.width / 2;
                const cy = displaySize.height / 2;
                const rx = (displaySize.width * 0.45) / 2;
                const ry = (displaySize.height * 0.70) / 2;

                const faceCx = box.x + box.width / 2;
                const faceCy = box.y + box.height / 2;
                const normalizedX = (faceCx - cx) / rx;
                const normalizedY = (faceCy - cy) / ry;
                const isInsideOval = (normalizedX * normalizedX + normalizedY * normalizedY) <= 1.0;

                const isCloseEnough = faceRatio >= 0.25;
                const isPositionedCorrectly = isCloseEnough && isInsideOval;

                // Só desenha landmarks e bounding box se o rosto estiver dentro da elipse
                if (isInsideOval) {
                    drawLandmarksByRegion(ctx, resized.landmarks, displaySize);

                    ctx.strokeStyle = isPositionedCorrectly ? 'rgba(40, 167, 69, 0.7)' : 'rgba(255, 193, 7, 0.7)';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(box.x, box.y, box.width, box.height);
                }
                // Se o rosto estiver fora da elipse, o canvas permanece limpo (sem box/landmarks externos)

                if (isPositionedCorrectly) {
                    guide?.classList.add('detected');
                    updateQualityIndicator('Rosto detectado — pronto para captura!', true);
                    document.getElementById('btn-capture-enrollment').disabled = false;
                } else {
                    guide?.classList.remove('detected');
                    if (!isInsideOval) {
                        updateQualityIndicator('Centralize seu rosto no círculo', false);
                    } else {
                        updateQualityIndicator('Aproxime-se mais da câmera', false);
                    }
                    document.getElementById('btn-capture-enrollment').disabled = true;
                }
            } else {
                guide?.classList.remove('detected');
                updateQualityIndicator('Posicione o rosto no centro', false);
                document.getElementById('btn-capture-enrollment').disabled = true;
            }
        } catch (err) {
            // Skip frame on overload
        }
    }, 250);
}

/**
 * Draws the 68 facial landmarks with color-coded regions.
 */
function drawLandmarksByRegion(ctx, landmarks, displaySize) {
    const points = landmarks.positions;

    const regions = [
        { range: [0, 16], color: LANDMARK_COLORS.jaw, size: 2 },
        { range: [17, 21], color: LANDMARK_COLORS.eyebrowL, size: 3 },
        { range: [22, 26], color: LANDMARK_COLORS.eyebrowR, size: 3 },
        { range: [27, 35], color: LANDMARK_COLORS.nose, size: 3 },
        { range: [36, 41], color: LANDMARK_COLORS.eyeL, size: 3.5 },
        { range: [42, 47], color: LANDMARK_COLORS.eyeR, size: 3.5 },
        { range: [48, 59], color: LANDMARK_COLORS.mouthOuter, size: 3 },
        { range: [60, 67], color: LANDMARK_COLORS.mouthInner, size: 2.5 }
    ];

    regions.forEach(region => {
        ctx.fillStyle = region.color;
        ctx.shadowColor = region.color;
        ctx.shadowBlur = 6;

        for (let i = region.range[0]; i <= region.range[1]; i++) {
            const pt = points[i];
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, region.size, 0, Math.PI * 2);
            ctx.fill();
        }

        // Connect points within the region with lines
        ctx.strokeStyle = region.color;
        ctx.lineWidth = 1;
        ctx.shadowBlur = 0;
        ctx.beginPath();
        for (let i = region.range[0]; i <= region.range[1]; i++) {
            const pt = points[i];
            if (i === region.range[0]) {
                ctx.moveTo(pt.x, pt.y);
            } else {
                ctx.lineTo(pt.x, pt.y);
            }
        }
        // Close eyes and mouth shapes
        if ([36, 42, 48, 60].includes(region.range[0])) {
            ctx.closePath();
        }
        ctx.stroke();
    });

    ctx.shadowBlur = 0;
}

/**
 * Captures the current frame, extracts descriptor + landmarks.
 */
window.captureEnrollmentPhoto = async function () {
    if (enrollmentState.isProcessing) return;
    enrollmentState.isProcessing = true;

    const video = document.getElementById('enrollment-video');
    const btnCapture = document.getElementById('btn-capture-enrollment');
    btnCapture.classList.add('capturing');
    btnCapture.disabled = true;

    try {
        const detection = await faceapi
            .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.4 }))
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            if (typeof showToast === 'function') showToast('Nenhum rosto detectado. Tente novamente.', 'error');
            enrollmentState.isProcessing = false;
            btnCapture.classList.remove('capturing');
            btnCapture.disabled = false;
            return;
        }

        // Capture frame as image, masking it to keep only pixels inside the delimited circle (ellipse)
        const captureCanvas = document.createElement('canvas');
        captureCanvas.width = video.videoWidth;
        captureCanvas.height = video.videoHeight;
        const capCtx = captureCanvas.getContext('2d');

        // Fill background with black
        capCtx.fillStyle = '#000000';
        capCtx.fillRect(0, 0, captureCanvas.width, captureCanvas.height);

        // Calculate centered ellipse matching the CSS guidelines (.face-oval width: 45%, height: 70%)
        const cx = captureCanvas.width / 2;
        const cy = captureCanvas.height / 2;
        const rx = (captureCanvas.width * 0.45) / 2;
        const ry = (captureCanvas.height * 0.70) / 2;

        // Clip to the ellipse guide and draw the video image
        capCtx.save();
        capCtx.beginPath();
        capCtx.ellipse(cx, cy, rx, ry, 0, 0, 2 * Math.PI);
        capCtx.clip();
        capCtx.drawImage(video, 0, 0);
        capCtx.restore();

        // Draw landmarks on the captured image
        const landmarks = detection.landmarks;
        const captureDisplaySize = { width: video.videoWidth, height: video.videoHeight };
        drawLandmarksByRegion(capCtx, landmarks, captureDisplaySize);

        const imageData = captureCanvas.toDataURL('image/jpeg', 0.8);

        // Extract landmark positions as simple array
        const landmarkPositions = landmarks.positions.map(p => ({
            x: parseFloat((p.x / video.videoWidth).toFixed(4)),
            y: parseFloat((p.y / video.videoHeight).toFixed(4))
        }));

        // Store capture
        enrollmentState.captures.push({
            descriptor: Array.from(detection.descriptor),
            landmarks: landmarkPositions,
            imageData: imageData
        });

        // Update preview
        const step = enrollmentState.currentStep;
        const slot = document.getElementById(`preview-slot-${step}`);
        if (slot) {
            slot.classList.add('captured');
            const ph = slot.querySelector('.preview-placeholder');
            ph.innerHTML = `<img src="${imageData}" alt="Captura ${step}">`;
        }

        // Advance to next step
        markStepDone(step);

        if (step < enrollmentState.totalSteps) {
            enrollmentState.currentStep = step + 1;
            activateStep(step + 1);
        } else {
            // All 3 photos captured
            document.getElementById('btn-finish-enrollment').disabled = false;
            btnCapture.disabled = true;
            updateQualityIndicator('Todas as capturas completas!', true);
        }

    } catch (err) {
        console.error('Enrollment capture error:', err);
        if (typeof showToast === 'function') showToast('Erro na captura. Tente novamente.', 'error');
    } finally {
        enrollmentState.isProcessing = false;
        btnCapture.classList.remove('capturing');
    }
};

/**
 * Finalizes the enrollment: averages descriptors and sends to registration.
 */
window.finalizeEnrollment = async function () {
    if (enrollmentState.captures.length < enrollmentState.totalSteps) {
        if (typeof showToast === 'function') showToast('Complete todas as capturas primeiro.', 'error');
        return;
    }

    // Average the 3 descriptors for better recognition accuracy
    const avgDescriptor = new Float32Array(128);
    enrollmentState.captures.forEach(cap => {
        cap.descriptor.forEach((val, idx) => {
            avgDescriptor[idx] += val / enrollmentState.totalSteps;
        });
    });



    // Package all data
    const faceData = {
        descriptor: Array.from(avgDescriptor),
        allDescriptors: enrollmentState.captures.map(c => c.descriptor),
        landmarks: enrollmentState.captures.map(c => c.landmarks),
        images: enrollmentState.captures.map(c => c.imageData),
        captureCount: enrollmentState.totalSteps
    };

    // Fluxo alternativo: cadastro direto de biometria para aluno existente
    if (enrollmentTargetStudentId) {
        try {
            if (typeof showToast === 'function') {
                showToast("Salvando biometria facial...", "info");
            }
            const res = await fetch('api.php?action=update_student_biometrics', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: enrollmentTargetStudentId,
                    face_descriptor: JSON.stringify(faceData.descriptor),
                    face_landmarks: JSON.stringify(faceData)
                })
            });
            const data = await res.json();

            if (data.success) {
                if (typeof showToast === 'function') {
                    showToast("Biometria atualizada com sucesso!", "success");
                }

                // Atualiza o cache local imediatamente para refletir o novo status biométrico
                if (window.knownStudentsCache) {
                    const cached = window.knownStudentsCache.find(s => s.id == enrollmentTargetStudentId);
                    if (cached) {
                        cached.face_descriptor = JSON.stringify(faceData.descriptor);
                        cached.face_landmarks = JSON.stringify(faceData);
                    }
                }

                // Encerra recursos e fecha o modal
                stopEnrollmentCamera();
                const faceModal = document.getElementById('modal-face-enrollment');
                if (faceModal) faceModal.classList.remove('active');

                resetEnrollmentState();

                // Recarrega o cache completo e atualiza as tabelas
                if (typeof loadStudents === 'function') {
                    await loadStudents();
                }
                if (typeof window.refreshStudentsDetailTable === 'function') {
                    window.refreshStudentsDetailTable();
                }

                // Re-liga o hardware de reconhecimento facial principal
                if (typeof window.startVideo === 'function') {
                    setTimeout(() => window.startVideo(), 500);
                }

            } else {
                if (typeof showToast === 'function') {
                    showToast(data.message || "Erro ao salvar biometria.", "error");
                }
            }
        } catch (err) {
            console.error("Erro ao salvar biometria:", err);
            if (typeof showToast === 'function') {
                showToast("Erro de rede ao salvar biometria.", "error");
            }
        }
        return;
    }

    // Set data in the registration form
    const regDescriptor = document.getElementById('reg-descriptor');
    const regFaceData = document.getElementById('reg-face-data');

    // Primary descriptor (average) for backward compatible recognition
    if (regDescriptor) {
        regDescriptor.value = JSON.stringify(faceData.descriptor);
    }

    // Full face data (landmarks + all descriptors + base64 images)
    if (regFaceData) {
        regFaceData.value = JSON.stringify(faceData);
    }

    // Update status displays
    const captureStatus = document.getElementById('face-capture-status');
    if (captureStatus) {
        captureStatus.innerText = `[Biometria Avançada Capturada — ${enrollmentState.totalSteps} ângulos ✓]`;
        captureStatus.style.color = 'var(--success)';
    }

    const captureCount = document.getElementById('face-capture-count');
    if (captureCount) {
        captureCount.innerText = `${enrollmentState.totalSteps}/${enrollmentState.totalSteps}`;
    }

    // Close enrollment wizard resources and open registration modal
    stopEnrollmentCamera();

    // Fecha o modal de biometria
    const faceModal = document.getElementById('modal-face-enrollment');
    if (faceModal) faceModal.classList.remove('active');

    // Abre modal de formulário
    const regModal = document.getElementById('modal-register');
    if (regModal) {
        regModal.classList.add('active');
        // Gatilho para preencher o DROPDOWN de Turmas no modal!
        if (typeof window.loadTurmasForSelect === 'function') {
            window.loadTurmasForSelect();
        }
    }

    // Re-liga o hardware principal
    if (typeof window.startVideo === 'function') {
        setTimeout(() => window.startVideo(), 500);
    }
};

// --- UI Helpers ---

function updateStepInstruction(step) {
    const config = STEP_CONFIG[step - 1];
    if (!config) return;

    const iconEl = document.querySelector('#enrollment-instruction .instruction-icon');
    const textEl = document.querySelector('#enrollment-instruction .instruction-text');

    if (iconEl) {
        iconEl.className = `fas ${config.icon} instruction-icon`;
    }
    if (textEl) {
        textEl.textContent = config.text;
    }
}

function markStepDone(step) {
    const stepEl = document.querySelector(`.progress-step[data-step="${step}"]`);
    if (stepEl) {
        stepEl.classList.remove('active');
        stepEl.classList.add('done');
    }

    // Mark progress line as done
    const lines = document.querySelectorAll('.progress-line');
    if (step <= lines.length) {
        lines[step - 1]?.classList.add('done');
    }
}

function activateStep(step) {
    const stepEl = document.querySelector(`.progress-step[data-step="${step}"]`);
    if (stepEl) {
        stepEl.classList.add('active');
    }
    updateStepInstruction(step);
}

function updateQualityIndicator(text, ready) {
    const el = document.getElementById('enrollment-quality');
    if (!el) return;

    el.innerHTML = ready
        ? `<i class="fas fa-check-circle"></i> ${text}`
        : `<i class="fas fa-circle-notch fa-spin"></i> ${text}`;

    el.classList.toggle('ready', ready);
}

// O acionamento manual fica a cargo do botão "Novo Cadastro" (openFaceEnrollment).

/**
 * Abre a modal para seleção de câmera e retorna a escolha.
 */
window.showCameraSelector = async function (onConfirm, onCancel) {
    const modal = document.getElementById('modal-camera-selector');
    const select = document.getElementById('camera-device-select');
    const btnConfirm = document.getElementById('btn-confirm-camera');

    if (!modal || !select || !btnConfirm) {
        // Fallback: se os elementos da modal não existirem, prossegue direto
        onConfirm();
        return;
    }

    // 1. Para a câmera principal de reconhecimento se estiver rodando
    if (window.localStream && typeof window.stopVideo === 'function') {
        await window.stopVideo();
    }

    try {
        // Solicita acesso rápido apenas para obter permissão (necessário para ler labels dos dispositivos)
        const tempStream = await navigator.mediaDevices.getUserMedia({ video: true });
        tempStream.getTracks().forEach(track => track.stop());

        // Enumera os dispositivos
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');

        if (videoDevices.length === 0) {
            showToast("Nenhuma câmera encontrada no sistema.", "error");
            if (onCancel) onCancel();
            if (typeof window.startVideo === 'function') window.startVideo();
            return;
        }

        // Limpa e popula a lista de opções
        select.innerHTML = '';
        videoDevices.forEach((device, index) => {
            const opt = document.createElement('option');
            opt.value = device.deviceId;
            opt.innerText = device.label || `Câmera ${index + 1}`;
            select.appendChild(opt);
        });

        // Configura ação de confirmação
        btnConfirm.onclick = () => {
            window.selectedCameraDeviceId = select.value;
            modal.classList.remove('active');
            onConfirm();
        };

        // Abre a modal de seleção
        modal.classList.add('active');

    } catch (err) {
        console.warn("Erro ao buscar câmeras:", err);
        showToast("Permissão de câmera negada ou erro de hardware.", "error");
        if (onCancel) onCancel();
        if (typeof window.startVideo === 'function') window.startVideo();
    }
};

/**
 * Fecha a modal de seleção de câmera.
 */
window.closeCameraSelector = function () {
    const modal = document.getElementById('modal-camera-selector');
    if (modal) {
        modal.classList.remove('active');
    }

    // Re-liga o hardware de reconhecimento facial principal
    if (typeof window.startVideo === 'function') {
        setTimeout(() => window.startVideo(), 500);
    }
};
