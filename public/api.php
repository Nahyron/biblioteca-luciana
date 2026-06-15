<?php

/**
 * Roteador Central da API (JSON).
 * Este arquivo recebe todas as requisições AJAX do frontend e encaminha para o controller adequado.
 */

// Importa as configurações globais e o autoloader
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/autoload.php';

// Camada de Infraestrutura
use App\Config\Database;
use App\Infrastructure\Persistence\MySQLStudentRepository;
use App\Infrastructure\Persistence\MySQLTurmaRepository;

// Camada de Aplicação (Serviços)
use App\Application\Services\StudentService;
use App\Application\Services\TurmaService;
use App\Application\Services\ExportService;

// Camada de Apresentação (Controllers)
use App\Presentation\Controllers\StudentController;
use App\Presentation\Controllers\TurmaController;
use App\Presentation\Controllers\ExportController;
use App\Presentation\Controllers\AdminController;

// Define o header de resposta como JSON para todas as requisições
header('Content-Type: application/json');

try {
    // 1. Inicializa a conexão (Infra)
    $db = (new Database())->getConnection();

    // 2. Instancia Repositórios (Infra)
    $studentRepo = new MySQLStudentRepository($db);
    $turmaRepo = new MySQLTurmaRepository($db);

    // 3. Instancia Serviços (Application)
    $studentService = new StudentService($studentRepo);
    $turmaService = new TurmaService($turmaRepo);
    $exportService = new ExportService($db);
    // 4. Instancia Controllers (Presentation) - Injetando dependências
    $studentController = new StudentController($studentService);
    $classController = new TurmaController($turmaService); // Mantido como $classController para consistência com o original
    $exportController = new ExportController($exportService);
    $adminController  = new AdminController($db);

    // Obtém a ação solicitada via URL (ex: api.php?action=list_students)
    $action = $_GET['action'] ?? '';

    // Roteamento manual baseado na ação
    switch ($action) {

        case 'list_students':
            echo json_encode($studentController->listAll());
            break;

        case 'list_history':
            $period = $_GET['period'] ?? 'all';
            $date   = $_GET['date']   ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            echo json_encode($studentController->listHistory($period, $date, $startDate, $endDate));
            break;

        case 'register_student':

            // Obtém dados JSON do corpo da requisição POST
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($studentController->register($data));
            break;

        case 'delete_student':
            $id = (int)($_GET['id'] ?? 0);
            $operadorId = \App\Infrastructure\Auth\SessionAuth::getAdminId();
            $operadorTipo = \App\Infrastructure\Auth\SessionAuth::getAdminTipo();
            $operadorNome = 'Sistema';
            
            if ($operadorId > 0) {
                $table = $operadorTipo === 'admin' ? 'admins' : 'professores';
                $stmtOp = $db->prepare("SELECT usuario FROM {$table} WHERE id = ? LIMIT 1");
                if ($stmtOp) {
                    $stmtOp->bind_param("i", $operadorId);
                    $stmtOp->execute();
                    $resOp = $stmtOp->get_result();
                    if ($resOp && $rowOp = $resOp->fetch_assoc()) {
                        $operadorNome = $rowOp['usuario'] . ' (' . ($operadorTipo === 'admin' ? 'Admin' : 'Professor') . ')';
                    }
                    $stmtOp->close();
                }
            }
            echo json_encode($studentController->delete($id, $operadorNome));
            break;

        case 'list_inactive_students':
            echo json_encode($studentController->listInactive());
            break;

        case 'activate_student':
            $id = (int)($_GET['id'] ?? 0);
            $operadorId = \App\Infrastructure\Auth\SessionAuth::getAdminId();
            $operadorTipo = \App\Infrastructure\Auth\SessionAuth::getAdminTipo();
            $operadorNome = 'Sistema';
            
            if ($operadorId > 0) {
                $table = $operadorTipo === 'admin' ? 'admins' : 'professores';
                $stmtOp = $db->prepare("SELECT usuario FROM {$table} WHERE id = ? LIMIT 1");
                if ($stmtOp) {
                    $stmtOp->bind_param("i", $operadorId);
                    $stmtOp->execute();
                    $resOp = $stmtOp->get_result();
                    if ($resOp && $rowOp = $resOp->fetch_assoc()) {
                        $operadorNome = $rowOp['usuario'] . ' (' . ($operadorTipo === 'admin' ? 'Admin' : 'Professor') . ')';
                    }
                    $stmtOp->close();
                }
            }
            echo json_encode($studentController->activate($id, $operadorNome));
            break;

        case 'inactive_alert':
            // Grava o alerta de aluno inativo no arquivo compartilhado para o SSE transmitir
            $data = json_decode(file_get_contents('php://input'), true);
            $alertId   = (int)($data['id']   ?? 0);
            $alertNome = htmlspecialchars(strip_tags($data['nome'] ?? ''), ENT_QUOTES, 'UTF-8');
            if ($alertId > 0 && $alertNome) {
                $alertsFile = dirname(__DIR__) . '/storage/alerts.json';
                $payload = json_encode([
                    'id'        => $alertId,
                    'nome'      => $alertNome,
                    'timestamp' => time(),
                ]);
                file_put_contents($alertsFile, $payload, LOCK_EX);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            }
            break;

        // --- Gestão de Turmas ---
        case 'list_classes':
            echo json_encode($classController->listAll());
            break;

        case 'create_class':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($classController->create($data));
            break;

        case 'delete_class':
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode($classController->delete($id));
            break;

        case 'update_class':
            $data = json_decode(file_get_contents('php://input'), true);
            $classId = (int)($data['id'] ?? 0);
            $newName = (string)($data['nome'] ?? '');
            echo json_encode($classController->updateName($classId, $newName));
            break;

        case 'update_student_class':
            $data = json_decode(file_get_contents('php://input'), true);
            $studentId = (int)($data['id'] ?? 0);
            $className = (string)($data['className'] ?? '');
            echo json_encode($studentController->updateClass($studentId, $className));
            break;

        // --- Controle de Acesso e Dashboard ---
        case 'record_access':
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode($studentController->recordAccess($id));
            break;

        case 'delete_history':
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode($studentController->deleteHistory($id));
            break;

        case 'update_student':
            $data = json_decode(file_get_contents('php://input'), true);
            $studentId = (int)($data['id'] ?? 0);
            $newName = (string)($data['nome'] ?? '');
            echo json_encode($studentController->updateName($studentId, $newName));
            break;

        case 'update_history':
            $data = json_decode(file_get_contents('php://input'), true);
            $logId = (int)($data['id'] ?? 0);
            $newDate = (string)($data['horario'] ?? '');
            echo json_encode($studentController->updateHistory($logId, $newDate));
            break;

        case 'dashboard_stats':
            $period = $_GET['period'] ?? 'today';
            $date = $_GET['date'] ?? null;
            $type = $_GET['type'] ?? 'all';
            echo json_encode($studentController->getDashboardStats($period, $date, $type));
            break;

        // --- Relatórios ---
        case 'export_excel':
            $period = $_GET['period'] ?? 'all';
            $date = $_GET['date'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $exportController->exportExcel($period, $date, $startDate, $endDate);
            break;

        case 'export_students':
            $exportController->exportStudents();
            break;

        case 'export_students_xls':
            $exportController->exportStudentsXls();
            break;

        case 'update_student_biometrics':
            $data = json_decode(file_get_contents('php://input'), true);
            $studentId = (int)($data['id'] ?? 0);
            echo json_encode($studentController->updateBiometrics($studentId, $data));
            break;

        case 'import_students_excel':
            $className = $_POST['className'] ?? '';
            if (empty($className)) {
                echo json_encode(['success' => false, 'message' => 'Turma não informada.']);
                break;
            }
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Arquivo não enviado ou com erro no upload.']);
                break;
            }
            echo json_encode($studentController->importFromExcel($_FILES['excel_file']['tmp_name'], $className));
            break;


        // --- Gerenciamento de Admins e Professores ---
        case 'list_admins':
            $tipo = preg_replace('/[^a-z]/', '', $_GET['tipo'] ?? 'admin');
            echo json_encode($adminController->listAll($tipo));
            break;

        case 'create_admin':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($adminController->create($data ?? []));
            break;

        case 'reset_admin_password':
            $id = (int)($_GET['id'] ?? 0);
            $currentTipo = \App\Infrastructure\Auth\SessionAuth::getAdminTipo();
            echo json_encode($adminController->resetPassword($id, $currentTipo));
            break;

        case 'delete_admin':
            $id         = (int)($_GET['id'] ?? 0);
            $currentId  = \App\Infrastructure\Auth\SessionAuth::getAdminId();
            $currentTipo = \App\Infrastructure\Auth\SessionAuth::getAdminTipo();
            echo json_encode($adminController->delete($id, $currentId, $currentTipo));
            break;

        case 'change_own_password':
            if (!\App\Infrastructure\Auth\SessionAuth::isAuthenticated()) {
                echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $novaSenha = $data['nova_senha'] ?? '';
            $currentId = \App\Infrastructure\Auth\SessionAuth::getAdminId();
            $currentTipo = \App\Infrastructure\Auth\SessionAuth::getAdminTipo();
            
            $res = $adminController->changeOwnPassword($currentId, $currentTipo, $novaSenha);
            if ($res['success']) {
                \App\Infrastructure\Auth\SessionAuth::setForcePasswordChange(false);
            }
            echo json_encode($res);
            break;

        // --- Fallback para ações desconhecidas ---
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida ou não informada']);
            break;
    }
} catch (Throwable $e) {
    // Captura qualquer erro (Exception ou Error) e retorna formatado como JSON
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => 'Erro interno no servidor: ' . $e->getMessage()
    ]);
}
