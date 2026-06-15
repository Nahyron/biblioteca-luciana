/**
 * Reconhecimento: Lógica de integração e reset de hardware
 */

async function retryCamera() {
    showToast("Tentando reiniciar hardware da câmera...", "success");
    try {
        if (typeof window.stopVideo === 'function') {
            await window.stopVideo();
        }
        if (typeof window.startVideo === 'function') {
            await window.startVideo();
        }
    } catch (err) {
        console.error("Erro no reset de hardware:", err);
        showToast("Não foi possível reiniciar a câmera.", "error");
    }
}
