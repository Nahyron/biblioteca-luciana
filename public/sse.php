<?php
/**
 * SSE Endpoint - Notificações em Tempo Real
 * Mantém uma conexão HTTP aberta e envia eventos de aluno inativo
 * para todos os clientes conectados ao sistema.
 */

// Desativa qualquer tipo de buffering de saída no PHP e Apache
while (ob_get_level()) {
    ob_end_clean();
}
if (ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 'Off');
}

require_once dirname(__DIR__) . '/config/config.php';

// Se houver sessão PHP ativa, fecha para liberar bloqueio de requisições concorrentes da mesma sessão
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Headers SSE obrigatórios
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Para nginx/Hostinger não bufferizar
header('Access-Control-Allow-Origin: *');

// Evita timeout do PHP (conexão longa)
set_time_limit(0);
ignore_user_abort(false);

$alertsFile = dirname(__DIR__) . '/storage/alerts.json';
$lastTimestamp = isset($_GET['since']) ? (int)$_GET['since'] : 0;

// Heartbeat inicial para confirmar a conexão
echo "event: connected\n";
echo "data: {\"status\":\"ok\"}\n\n";
ob_flush();
flush();

// Loop de escuta SSE (máximo 30 segundos no Windows/Apache para liberar threads do PHP mais rápido)
$startTime = time();
$maxDuration = 30;

while (!connection_aborted() && (time() - $startTime) < $maxDuration) {

    if (file_exists($alertsFile)) {
        $raw = @file_get_contents($alertsFile);
        if ($raw) {
            $alert = json_decode($raw, true);

            // Só emite se houver um alerta válido mais novo que o último recebido pelo cliente
            if (
                isset($alert['timestamp']) &&
                $alert['timestamp'] > 0 &&
                $alert['timestamp'] > $lastTimestamp &&
                (time() - $alert['timestamp']) < 15  // Ignora alertas com mais de 15 segundos
            ) {
                $lastTimestamp = $alert['timestamp'];
                $payload = json_encode([
                    'id'        => $alert['id'],
                    'nome'      => $alert['nome'],
                    'timestamp' => $alert['timestamp'],
                ]);

                echo "event: inactive_alert\n";
                echo "data: {$payload}\n\n";
                ob_flush();
                flush();
            }
        }
    }

    // Heartbeat a cada 10s para manter a conexão viva
    if ((time() - $startTime) % 10 === 0) {
        echo "event: heartbeat\n";
        echo "data: {\"ts\":" . time() . "}\n\n";
        ob_flush();
        flush();
    }

    sleep(1); // Verifica a cada 1 segundo (mais responsivo)
}

// Ao encerrar, o cliente receberá o fechamento HTTP e seu `onerror` fará a reconexão automática com a URL atualizada
ob_flush();
flush();
