/**
 * SSE Client - Sistema de Notificações em Tempo Real
 * Conecta ao endpoint SSE e escuta eventos de aluno inativo,
 * exibindo a modal de alerta em todos os clientes conectados.
 */

(function () {
    'use strict';

    let eventSource = null;
    let lastTimestamp = 0;
    let reconnectTimer = null;

    /**
     * Exibe a modal de alerta de aluno inativo por 2 segundos.
     * @param {string} nome - Nome do aluno inativo detectado.
     */
    window.showInactiveModal = function (nome) {
        const modal = document.getElementById('modal-inactive-alert');
        const nameEl = document.getElementById('inactive-alert-name');

        if (!modal) return;

        if (nameEl) nameEl.textContent = nome;
        modal.classList.add('active');

        setTimeout(() => {
            modal.classList.remove('active');
        }, 3000);
    };

    /**
     * Inicializa a conexão SSE e configura os listeners de eventos.
     */
    function connectSSE() {
        // Se já existe uma conexão, garante o fechamento limpo
        if (eventSource) {
            try {
                eventSource.close();
            } catch (e) {}
            eventSource = null;
        }

        // Limpa timers de reconexão pendentes
        if (reconnectTimer) {
            clearTimeout(reconnectTimer);
            reconnectTimer = null;
        }

        const url = `sse.php?since=${lastTimestamp}`;
        eventSource = new EventSource(url);

        // Confirmação de conexão estabelecida
        eventSource.addEventListener('connected', () => {
            console.log('[SSE] Conexão com servidor de notificações estabelecida.');
        });

        // Evento principal: aluno inativo detectado
        eventSource.addEventListener('inactive_alert', (e) => {
            try {
                const data = JSON.parse(e.data);

                // Evita duplicatas: só processa se for mais novo que o último
                if (data.timestamp <= lastTimestamp) return;
                lastTimestamp = data.timestamp;

                console.log(`[SSE] Alerta recebido: ${data.nome} (ID: ${data.id})`);
                window.showInactiveModal(data.nome);
            } catch (err) {
                console.warn('[SSE] Erro ao processar evento:', err);
            }
        });

        // Heartbeat: apenas mantém a conexão viva
        eventSource.addEventListener('heartbeat', () => {});

        // Reconecta em caso de erro de rede ou fechamento normal da conexão pelo backend
        eventSource.onerror = () => {
            console.warn('[SSE] Conexão perdida ou reiniciada. Tentando reconectar...');
            if (eventSource) {
                try {
                    eventSource.close();
                } catch (e) {}
                eventSource = null;
            }
            // Agenda uma reconexão limpa após 2 segundos
            if (reconnectTimer) clearTimeout(reconnectTimer);
            reconnectTimer = setTimeout(connectSSE, 2000);
        };
    }

    // Inicia a conexão SSE quando a página terminar de carregar
    document.addEventListener('DOMContentLoaded', () => {
        // Pequeno delay para garantir que os outros scripts já inicializaram
        setTimeout(connectSSE, 1000);
    });

    // Fecha a conexão quando o usuário sair da página
    window.addEventListener('beforeunload', () => {
        if (eventSource) {
            try {
                eventSource.close();
            } catch (e) {}
            eventSource = null;
        }
        if (reconnectTimer) {
            clearTimeout(reconnectTimer);
        }
    });
})();
