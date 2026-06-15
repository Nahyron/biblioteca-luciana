<?php
/**
 * TERMINAL TOTEM - SISTEMA DE BIBLIOTECA (AUTOATENDIMENTO BIOMÉTRICO)
 * -------------------------------------------------------------------
 * Interface limpa em tela cheia, focada 100% no reconhecimento facial.
 */
$currentPage = 'totem';

require_once dirname(__DIR__) . '/config/config.php';
header("Cache-Control: no-cache, must-revalidate");

// 1. Inclui o cabeçalho base do sistema
include_once PUBLIC_PATH . '/views/partials/head.php';
?>

<!-- Folha de estilo específica para a interface Premium do Totem -->
<link rel="stylesheet" href="assets/css/modules/totem.css?v=<?php echo time(); ?>">

<body class="totem-body">

    <div class="totem-container">

        <!-- CÂMERA EM TELA CHEIA -->
        <div class="totem-camera-wrapper">
            <video id="video" autoplay muted></video>
            <canvas id="overlay"></canvas>
            
            <!-- Delimitação Visual no meio para encaixar o rosto -->
            <div class="face-scanner-box scanning">
                <div class="face-scanner-laser"></div>
            </div>

            <!-- Placeholder enquanto carrega a câmera -->
            <div id="camera-placeholder" class="totem-camera-placeholder">
                <i class="fas fa-video-slash"></i>
                <span>Aguardando sinal de vídeo...</span>
            </div>
        </div>

        <!-- CABEÇALHO DO TERMINAL -->
        <header class="totem-header">
            <div class="totem-brand">
                <div class="totem-logo"><?php echo APP_NAME_SHORT; ?></div>
                <div class="totem-title">TERMINAL DE ACESSO</div>
                <button id="btn-change-camera-totem" class="totem-btn-camera" onclick="window.showCameraSelector(window.startVideo, () => {})" title="Alterar Câmera">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
            
            <div class="totem-time-box">
                <!-- Mostrado via JS de forma dinâmica -->
                <div id="totem-clock-value" class="totem-clock">00:00:00</div>
                <div id="totem-date-value" class="totem-date">Carregando data...</div>
            </div>
        </header>

        <!-- RODAPÉ E MONITOR DE STATUS -->
        <footer class="totem-footer">
            <div class="totem-status-card">
                <div id="vision-status" class="totem-status-indicator">Iniciando Sistema...</div>
                <div id="vision-msg" class="totem-status-subtext">Aguardando inicialização da inteligência artificial.</div>
            </div>
        </footer>

    </div>

    <!-- 2. Componentes Flutuantes (Toasts) -->
    <?php include_once PUBLIC_PATH . '/views/partials/toasts.php'; ?>
    <?php include_once PUBLIC_PATH . '/views/modals/camera-selector.php'; ?>

    <!-- 3. Scripts e Autoloaders da IA -->
    <?php include_once PUBLIC_PATH . '/views/partials/scripts.php'; ?>

    <!-- 4. Lógica de Inicialização Automática do Totem -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Totem: Inicializando relógio e ativando webcam automática...');

            // Formatação de data no padrão BR
            const updateTotemDate = () => {
                const dateEl = document.getElementById('totem-date-value');
                if (dateEl) {
                    const now = new Date();
                    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                    dateEl.innerText = now.toLocaleDateString('pt-BR', options).toUpperCase();
                }
            };
            
            // Relógio local em tempo real
            const updateTotemClock = () => {
                const clockEl = document.getElementById('totem-clock-value');
                if (clockEl) {
                    const now = new Date();
                    clockEl.innerText = now.toLocaleTimeString('pt-BR');
                }
            };
            
            updateTotemDate();
            updateTotemClock();
            setInterval(updateTotemClock, 1000);

            // Substitui elementos de feedback padrão no script facial para usar os seletores corretos do Totem
            const subtextEl = document.getElementById('vision-msg');
            
            window.customSetVisionStatus = function(text, color) {
                const statusEl = document.getElementById('vision-status');
                if (statusEl) {
                    statusEl.innerText = text;
                    statusEl.style.color = color || '#fff';
                }
                
                // Mapeia mensagens do sistema para dar subtextos amigáveis ao aluno
                if (subtextEl) {
                    if (text.includes("Identificado")) {
                        subtextEl.innerText = "Acesso autorizado! Seja bem-vindo à biblioteca.";
                        subtextEl.style.color = "var(--success)";
                    } else if (text.includes("Desconhecido")) {
                        subtextEl.innerText = "Não encontramos sua biometria. Procure a recepção.";
                        subtextEl.style.color = "#dc3545";
                    } else if (text.includes("Alinhe")) {
                        subtextEl.innerText = "Mantenha o rosto dentro da área central.";
                        subtextEl.style.color = "#ffc107";
                    } else if (text.includes("Aproxime")) {
                        subtextEl.innerText = "Aproxime seu rosto para o escaneamento facial.";
                        subtextEl.style.color = "#ffc107";
                    } else if (text.includes("Aguardando Rosto")) {
                        subtextEl.innerText = "Posicione seu rosto dentro da elipse iluminada.";
                        subtextEl.style.color = "#a0aec0";
                    } else if (text.includes("Online")) {
                        subtextEl.innerText = "Scanner facial ativado. Posicione-se para o registro.";
                        subtextEl.style.color = "#a0aec0";
                    }
                }
            };

            // Liga a câmera automaticamente após carregar chamando o seletor
            setTimeout(() => {
                if (typeof window.showCameraSelector === 'function') {
                    window.showCameraSelector(window.startVideo, () => {
                        console.log('Seleção de câmera cancelada no totem, iniciando padrão...');
                    });
                } else if (typeof window.startVideo === 'function') {
                    window.startVideo();
                }
            }, 600);
        });

        // Evento para desativar a câmera caso o usuário feche a aba
        window.addEventListener('beforeunload', () => {
            if (typeof window.stopVideo === 'function') {
                window.stopVideo();
            }
        });
    </script>

</body>
</html>
