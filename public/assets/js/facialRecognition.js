/**
 * Módulo de Reconhecimento Facial (Cérebro do Sistema de Visão)
 * Responsável por carregar os modelos neurais, gerenciar o hardware da câmera 
 * e realizar a comparação biométrica em tempo real.
 */

console.log('SISTEMA_BIBLIOTECA: Módulo de reconhecimento facial ativo.');

// --- Variáveis de Controle Global (Cuidado ao modificar) ---
window.localStream = null;          // Armazena o fluxo de vídeo atual vindo da webcam
window.modelsLoaded = false;        // Indica se as redes neurais (TensorFlow.js) já estão prontas
window.lastFaceDescriptor = null;   // Cache da última biometria lida (Array de 128 posições float)
window.recognitionInterval = null;  // Identificador do Timer que roda o loop de reconhecimento
window.isHardwareChanging = false;  // Trava de segurança para evitar que o navegador trave ao ligar/desligar rápido

/**
 * Mapeia os elementos físicos do HTML que compõem a interface de visão.
 */
function getCamElements() {
    return {
        video: document.getElementById('video'),        // Onde a imagem da câmera aparece
        canvas: document.getElementById('overlay'),     // Onde desenhamos as marcações faciais
        status: document.getElementById('vision-status'), // Indicador visual de status (Online/Offline)
        placeholder: document.getElementById('camera-placeholder') // Overlay cinza de "Câmera Desligada"
    };
}

/**
 * Solicita autorização do usuário e liga o hardware da câmera.
 * Configura o sensor para uma resolução equilibrada (640x480).
 */
window.startVideo = async function () {
    // Evita race-conditions se o usuário clicar no botão freneticamente
    if (window.isHardwareChanging) return;
    window.isHardwareChanging = true;

    const el = getCamElements();

    // Se já estiver rodando, apenas ignora para não sobrecarregar
    if (window.localStream) {
        window.isHardwareChanging = false;
        return;
    }

    try {
        // Verifica suporte básico de hardware do navegador
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error('Hardware de vídeo não acessível nesta plataforma.');
        }

        const constraints = {
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        };

        if (window.selectedCameraDeviceId) {
            constraints.video.deviceId = { exact: window.selectedCameraDeviceId };
        } else {
            constraints.video.facingMode = "user"; // Prioriza câmera frontal em notebooks/celulares
        }

        // Tenta capturar o Stream com configurações ideais para web
        let stream;
        try {
            stream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch (err) {
            console.warn("Falha ao abrir câmera selecionada no fluxo principal, tentando padrão...", err);
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            });
        }

        if (el.video) {
            el.video.srcObject = stream;
            window.localStream = stream;

            el.video.onloadedmetadata = () => {
                el.video.play();
                // Remove o placeholder e atualiza o botão para estado ativo
                if (el.placeholder) el.placeholder.classList.add('hidden');
                updateCameraButton(true);

                // Orquestra o carregamento das IAs se for a primeira vez
                if (window.modelsLoaded) {
                    setVisionStatus("Sistema Online", "var(--success)");
                    if (!window.recognitionInterval) startRecognitionLoop();
                } else {
                    setVisionStatus("Carregando IA...", "#ffc107");
                    loadModels();
                }
            };
        }
    } catch (err) {
        // Tratamento de erros amigável para o usuário comum
        if (err.name === 'NotReadableError') {
            showToast('Outro programa está usando sua câmera agora.', 'error');
        } else if (err.name === 'NotAllowedError') {
            showToast('Acesso à câmera bloqueado no navegador.', 'warning');
        } else {
            showToast('Falha técnica de hardware detectada.', 'error');
        }
        setVisionStatus("Hardware Bloqueado", "#dc3545");
    } finally {
        window.isHardwareChanging = false;
    }
}

/**
 * Lógica manual para alternar entre Ligar e Desligar a câmera.
 */
window.toggleCamera = async function () {
    if (window.localStream) {
        await window.stopVideo();
    } else {
        await window.startVideo();
    }
}

/**
 * Encerra o hardware da câmera e todos os loops de processamento.
 * Essencial para liberar o recurso quando o usuário troca de aba.
 */
window.stopVideo = async function () {
    if (window.isHardwareChanging) return;
    window.isHardwareChanging = true;

    const el = getCamElements();

    // 1. Mata o loop de busca facial
    if (window.recognitionInterval) {
        clearInterval(window.recognitionInterval);
        window.recognitionInterval = null;
    }

    // 2. Libera o driver de vídeo
    if (window.localStream) {
        window.localStream.getTracks().forEach(track => {
            track.stop();
            console.log(`Driver: Sensor ${track.label} liberado.`);
        });
        window.localStream = null;
    }

    if (el.video) {
        el.video.pause();
        el.video.srcObject = null;
    }

    // Aguarda o sistema operacional reagir para evitar "Device Busy" em religadas rápidas
    await new Promise(resolve => setTimeout(resolve, 300));

    updateCameraButton(false);

    // Limpa desenhos residuais do canvas de overlay
    if (el.canvas) {
        el.canvas.getContext('2d').clearRect(0, 0, el.canvas.width, el.canvas.height);
    }

    setVisionStatus("Scanner Offline", "#666");
    if (el.placeholder) el.placeholder.classList.remove('hidden');

    window.isHardwareChanging = false;
}

/**
 * Altera visualmente o botão de controle da câmera.
 * @param {boolean} active Se a câmera está ligada ou não.
 */
function updateCameraButton(active) {
    const btn = document.getElementById('btn-toggle-camera');
    if (btn) {
        if (active) {
            btn.innerHTML = '<i class="fas fa-video-slash"></i> Desligar Câmera';
            btn.classList.replace('btn-primary', 'btn-secondary');
        } else {
            btn.innerHTML = '<i class="fas fa-video"></i> Ligar Câmera';
            btn.classList.replace('btn-secondary', 'btn-primary');
        }
    }
}

/**
 * Carrega os modelos neurais SSD e LANDMARKS da biblioteca face-api.
 */
async function loadModels() {
    // Endereço oficial das redes neurais pré-treinadas
    const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights';

    try {
        // Carrega simultaneamente as 3 redes necessárias para reconhecimento 1:N
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL), // Detecção rápida
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL), // Mapeamento de pontos (olhos, boca)
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL) // Extração de biometria
        ]);

        window.modelsLoaded = true;
        setVisionStatus("IA Pronta", "var(--success)");
        startRecognitionLoop(); // Inicia o loop assim que os modelos baixarem
    } catch (err) {
        setVisionStatus("Falha Crítica na IA", "#dc3545");
    }
}

/**
 * Loop de Reconhecimento: Processa frame a frame em busca de rostos conhecidos.
 * Rodando a 1Hz (1 vez por segundo) para máxima eficiência energética.
 */
function startRecognitionLoop() {
    window.recognitionInterval = setInterval(async () => {
        const el = getCamElements();
        if (!el.video || el.video.paused || !window.localStream) return;

        // Ajusta o tamanho da área de desenho para bater com o vídeo real
        const displaySize = { width: el.video.offsetWidth, height: el.video.offsetHeight };
        faceapi.matchDimensions(el.canvas, displaySize);

        try {
            // FASE 1: Detecção e Extração
            const detections = await faceapi.detectAllFaces(el.video, new faceapi.TinyFaceDetectorOptions({ inputSize: 160 }))
                .withFaceLandmarks()
                .withFaceDescriptors();

            // Limpa o canvas antes de desenhar novas molduras
            const ctx = el.canvas.getContext('2d', { willReadFrequently: true });
            ctx.clearRect(0, 0, el.canvas.width, el.canvas.height);

            if (detections.length > 0) {
                // Redimensiona as detecções para o tamanho de exibição CSS real da tela do totem
                const resizedDetections = faceapi.resizeResults(detections, displaySize);

                // Seleciona apenas o rosto principal (centralizado no tamanho de exibição)
                const mainFace = resizedDetections[0];
                const box = mainFace.detection.box;

                // FASE 2: Validar Proximidade ou Delimitação no Totem
                const totemScanner = document.querySelector('.face-scanner-box');
                if (totemScanner) {
                    // Centro do contêiner correspondente ao scanner posicionado a 40% do topo
                    const centerX = displaySize.width / 2;
                    const centerY = displaySize.height * 0.40;

                    // Centro do rosto detectado (nas coordenadas reais da tela)
                    const faceCenterX = box.x + (box.width / 2);
                    const faceCenterY = box.y + (box.height / 2);

                    // Tolerâncias para centralização (aumentada para 18% para tornar a captura mais responsiva)
                    const maxOffset = displaySize.width * 0.18;
                    const isAlignedX = Math.abs(faceCenterX - centerX) < maxOffset;
                    const isAlignedY = Math.abs(faceCenterY - centerY) < (maxOffset * 1.25);

                    // Tamanho ideal do rosto no Totem (nem muito longe, nem muito colado na câmera)
                    const minWidth = displaySize.width * 0.20;
                    const isProperSize = box.width >= minWidth;
                    const isInsideZone = isAlignedX && isAlignedY;

                    // Só desenha o bounding box se o rosto estiver dentro da zona delimitada
                    if (isInsideZone && isProperSize) {
                        const boxCtx = el.canvas.getContext('2d');
                        boxCtx.strokeStyle = 'rgba(40, 167, 69, 0.85)';
                        boxCtx.lineWidth = 2;
                        boxCtx.strokeRect(box.x, box.y, box.width, box.height);
                    }

                    if (!isAlignedX || !isAlignedY) {
                        setVisionStatus("Alinhe seu Rosto", "#ffc107");
                        totemScanner.style.borderColor = "#ffc107";
                        totemScanner.style.boxShadow = "0 0 0 9999px rgba(13, 13, 15, 0.78), 0 0 20px rgba(255, 193, 7, 0.3)";
                        return;
                    }

                    if (!isProperSize) {
                        setVisionStatus("Aproxime-se", "#ffc107");
                        totemScanner.style.borderColor = "#ffc107";
                        totemScanner.style.boxShadow = "0 0 0 9999px rgba(13, 13, 15, 0.78), 0 0 20px rgba(255, 193, 7, 0.3)";
                        return;
                    }

                    // Rosto perfeitamente posicionado e pronto para escaneamento
                    totemScanner.style.borderColor = "var(--success)";
                    totemScanner.style.boxShadow = "0 0 0 9999px rgba(13, 13, 15, 0.78), 0 0 30px rgba(40, 167, 69, 0.5)";
                } else {
                    // Tela padrão (sem Totem): desenha box apenas no rosto centralizado com tamanho mínimo
                    const faceCenterX = box.x + (box.width / 2);
                    const faceCenterY = box.y + (box.height / 2);
                    const centerX = displaySize.width / 2;
                    const centerY = displaySize.height / 2;
                    const maxOffset = displaySize.width * 0.25;
                    const isCentered = Math.abs(faceCenterX - centerX) < maxOffset && Math.abs(faceCenterY - centerY) < maxOffset;

                    if (box.width < (displaySize.width * 0.15)) {
                        setVisionStatus("Aproxime-se do Totem", "#ffc107");
                        return;
                    }

                    // Só desenha box no rosto dentro da área central
                    if (isCentered) {
                        const boxCtx = el.canvas.getContext('2d');
                        boxCtx.strokeStyle = 'rgba(40, 167, 69, 0.85)';
                        boxCtx.lineWidth = 2;
                        boxCtx.strokeRect(box.x, box.y, box.width, box.height);
                    }
                }


                // FASE 3: Comparação Biométrica 1 para N
                if (!window.knownStudentsCache) {
                    console.warn("Reconhecimento Facial: Cache de alunos nulo. Tentando carregar ativos...");
                    if (typeof loadStudents === 'function') {
                        loadStudents();
                    }
                    return;
                }

                let bestMatch = null;
                let minDistance = 0.60; // Limiar de aceitação calibrado para totem (menor = mais seguro, mais exigente)

                window.knownStudentsCache.forEach(student => {
                    if (student.face_descriptor) {
                        // Try to get all descriptors from face_landmarks (multi-angle enrollment)
                        let descriptorsToCompare = [];

                        if (student.face_landmarks) {
                            try {
                                const landmarkData = JSON.parse(student.face_landmarks);
                                if (landmarkData.allDescriptors && Array.isArray(landmarkData.allDescriptors)) {
                                    descriptorsToCompare = landmarkData.allDescriptors.map(d => new Float32Array(d));
                                }
                            } catch (e) { /* Fallback to single descriptor */ }
                        }

                        // Fallback: use primary descriptor
                        if (descriptorsToCompare.length === 0) {
                            descriptorsToCompare = [new Float32Array(JSON.parse(student.face_descriptor))];
                        }

                        // Find the minimum distance across all stored descriptors
                        let bestStudentDistance = Infinity;
                        descriptorsToCompare.forEach(storedDesc => {
                            const dist = faceapi.euclideanDistance(mainFace.descriptor, storedDesc);
                            if (dist < bestStudentDistance) bestStudentDistance = dist;
                        });

                        if (bestStudentDistance < minDistance) {
                            minDistance = bestStudentDistance;
                            bestMatch = student;
                        }
                    }
                });

                // Ação final: Aluno Reconhecido!
                if (bestMatch) {
                    // Verifica se o aluno está ativo antes de registrar o acesso
                    if (bestMatch.status === 'inativo') {
                        setVisionStatus(`Acesso Negado: ${bestMatch.nome}`, "#dc3545");
                        if (window.lastLoggedId !== `inactive_${bestMatch.id}`) {
                            window.lastLoggedId = `inactive_${bestMatch.id}`;
                            triggerInactiveAlert(bestMatch);

                            // Registra o acesso silenciosamente no banco para contar no dashboard
                            sendAccessLog(bestMatch, true);

                            setTimeout(() => {
                                if (window.lastLoggedId === `inactive_${bestMatch.id}`) {
                                    window.lastLoggedId = null;
                                }
                            }, 5000);
                        }
                    } else {
                        setVisionStatus(`Identificado: ${bestMatch.nome}`, "var(--success)");
                        if (window.lastLoggedId !== bestMatch.id) {
                            await sendAccessLog(bestMatch);
                        }
                    }
                } else {
                    setVisionStatus("Rosto Desconhecido", "#dc3545");
                }
            } else {
                setVisionStatus("Aguardando Rosto...", "var(--success)");
            }
        } catch (err) {
            console.warn('Processamento de frame pulado devido a carga.');
        }
    }, 1000);
}

/**
 * Dispara o alerta de aluno inativo:
 * - Exibe modal local (no totem ou admin)
 * - Notifica o servidor para propagar via SSE a todos os clientes
 */
async function triggerInactiveAlert(student) {
    // 1. Exibe a modal localmente de imediato
    if (typeof window.showInactiveModal === 'function') {
        window.showInactiveModal(student.nome);
    }

    // 2. Notifica o servidor para transmitir via SSE a todos os outros clientes
    try {
        await fetch('api.php?action=inactive_alert', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: student.id, nome: student.nome })
        });
    } catch (e) {
        console.warn('[Inativo] Falha ao notificar servidor:', e);
    }
}


/**
 * Atualiza o texto e a cor do monitor de biometria.
 */
function setVisionStatus(text, color) {
    // Se houver um manipulador global customizado (ex: no Totem), delega para ele
    if (window.customSetVisionStatus && typeof window.customSetVisionStatus === 'function') {
        window.customSetVisionStatus(text, color);
        return;
    }
    const el = getCamElements();
    if (el.status) {
        el.status.innerText = text;
        el.status.style.color = "white";
        el.status.style.background = color || "var(--gray-light)";
    }
}

/**
 * Persiste a entrada no banco de dados.
 */
async function sendAccessLog(student, isSilent = false) {
    if (!isSilent) {
        window.lastLoggedId = student.id;
    }
    try {
        const res = await fetch(`api.php?action=record_access&id=${student.id}`);
        const data = await res.json();

        if (data.success) {
            if (!isSilent) {
                // Abre o modal de sucesso premium com animação por 2 segundos
                const modalSuccess = document.getElementById('modal-recognition-success');
                const nameEl = document.getElementById('success-student-name');
                if (modalSuccess && nameEl) {
                    nameEl.innerText = student.nome;
                    modalSuccess.classList.add('active');

                    setTimeout(() => {
                        modalSuccess.classList.remove('active');
                    }, 2000);
                } else {
                    // Fallback de toast caso o modal não esteja presente na página
                    showToast(`Entrada registrada: ${student.nome}`);
                }
            }

            if (typeof loadStudents === 'function') loadStudents();

            if (!isSilent) {
                setTimeout(() => { window.lastLoggedId = null; }, 30000); // 30 segundos de trava contra duplicidade
            }
        } else {
            if (!isSilent) {
                // Se falhar no retorno da API, libera a trava mais cedo (5s) para tentar novamente
                setTimeout(() => {
                    if (window.lastLoggedId === student.id) window.lastLoggedId = null;
                }, 5000);
            }
        }
    } catch (e) {
        console.error('Falha ao logar acesso.');
        if (!isSilent) {
            // Se houver erro de rede, libera a trava mais cedo (5s) para tentar novamente
            setTimeout(() => {
                if (window.lastLoggedId === student.id) window.lastLoggedId = null;
            }, 5000);
        }
    }
}

// Inicializa o estado visual como desativado
document.addEventListener('DOMContentLoaded', () => {
    console.log('facialRecognition.js: Inicializando controles...');

    // Vincula o botão de toggle (redundância de segurança ao onclick do HTML)
    const btnToggle = document.getElementById('btn-toggle-camera');
    if (btnToggle) {
        btnToggle.addEventListener('click', () => {
            if (typeof window.toggleCamera === 'function') window.toggleCamera();
        });
    }

    setVisionStatus("Scanner Desativado", "#666");
    const el = getCamElements();
    if (el.placeholder) el.placeholder.classList.remove('hidden');
});
