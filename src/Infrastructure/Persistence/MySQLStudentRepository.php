<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\StudentRepositoryInterface;
use App\Domain\Entities\Student;
use mysqli;

/**
 * Implementação SQL da persistência de Alunos e Logs.
 * Camada de Infraestrutura: Centraliza as queries SQL originais migradas do Model.
 */
class MySQLStudentRepository implements StudentRepositoryInterface
{
    private string $table = "usuarios";

    public function __construct(private mysqli $db) {}

    public function getAll(): array
    {
        $today = date('Y-m-d');
        $sql = "SELECT u.id, u.nome, u.turma, u.criado_at, u.rosto_cadastrado_at, u.ultima_entrada_at, u.face_descriptor, u.face_landmarks,
                       (SELECT COUNT(*) FROM acessos_log al WHERE al.usuario_id = u.id AND DATE(al.horario_entrada) = ?) as daily_access_count,
                       (SELECT MAX(horario_entrada) FROM acessos_log al WHERE al.usuario_id = u.id) as last_entry
                FROM {$this->table} u 
                WHERE u.status = 'ativo'
                ORDER BY last_entry DESC, u.nome ASC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    public function getById(int $id): ?Student
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND status = 'ativo'");
        if (!$stmt) return null;

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if (!$data) return null;

        return new Student(
            $data['id'],
            $data['nome'],
            $data['turma'],
            $data['face_descriptor'],
            $data['criado_at'],
            $data['rosto_cadastrado_at'],
            $data['ultima_entrada_at']
        );
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO {$this->table} (nome, turma, face_descriptor, face_landmarks, foto_frontal, foto_esquerda, foto_direita, rosto_cadastrado_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $landmarks = $data['face_landmarks'] ?? null;
        $fFrontal = $data['foto_frontal'] ?? null;
        $fEsquerda = $data['foto_esquerda'] ?? null;
        $fDireita = $data['foto_direita'] ?? null;
        
        $descriptor = $data['face_descriptor'] ?? null;
        $rostoCadastradoAt = !empty($descriptor) ? date('Y-m-d H:i:s') : null;
        
        $stmt->bind_param("ssssssss", $data['nome'], $data['turma'], $descriptor, $landmarks, $fFrontal, $fEsquerda, $fDireita, $rostoCadastradoAt);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET nome = ? WHERE id = ?");
        if (!$stmt) return false;

        $stmt->bind_param("si", $data['nome'], $id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function delete(int $id, ?string $operador = null): bool
    {
        // 1. Apenas atualiza o status do aluno para 'inativo'
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'inativo' WHERE id = ?");
        if (!$stmt) return false;

        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();

        // 2. Registra o evento de desativação no histórico (acessos_log)
        if ($result) {
            $stmtLog = $this->db->prepare("INSERT INTO acessos_log (usuario_id, acao, operador) VALUES (?, 'desativacao', ?)");
            if ($stmtLog) {
                $stmtLog->bind_param("is", $id, $operador);
                $stmtLog->execute();
                $stmtLog->close();
            }
        }

        return $result;
    }

    public function deleteHistory(int $logId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM acessos_log WHERE id = ?");
        if (!$stmt) return false;

        $stmt->bind_param("i", $logId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function updateHistory(int $logId, string $newDate): bool
    {
        $stmt = $this->db->prepare("UPDATE acessos_log SET horario_entrada = ? WHERE id = ?");
        if (!$stmt) return false;

        $stmt->bind_param("si", $newDate, $logId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function updateClass(int $id, string $className): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET turma = ? WHERE id = ?");
        if (!$stmt) return false;

        $stmt->bind_param("si", $className, $id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function logAccess(int $studentId): bool
    {
        // Proteção Anti-Flood / Duplicidade no Backend (30 segundos)
        $stmtCheck = $this->db->prepare("SELECT horario_entrada FROM acessos_log WHERE usuario_id = ? ORDER BY horario_entrada DESC LIMIT 1");
        if ($stmtCheck) {
            $stmtCheck->bind_param("i", $studentId);
            $stmtCheck->execute();
            $res = $stmtCheck->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $lastAccess = strtotime($row['horario_entrada']);
                $now = time();
                if (($now - $lastAccess) < 30) {
                    // Ignora a gravação silenciosamente pois já foi registrado um acesso recente (menos de 30s)
                    $stmtCheck->close();
                    return true;
                }
            }
            $stmtCheck->close();
        }

        $stmtLog = $this->db->prepare("INSERT INTO acessos_log (usuario_id) VALUES (?)");
        if ($stmtLog) {
            $stmtLog->bind_param("i", $studentId);
            $stmtLog->execute();
            $stmtLog->close();
        }

        $stmtUpdate = $this->db->prepare("UPDATE {$this->table} SET ultima_entrada_at = NOW() WHERE id = ?");
        if ($stmtUpdate) {
            $stmtUpdate->bind_param("i", $studentId);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }

        return true;
    }

    public function getDashboardStats(string $period = 'today', ?string $date = null, ?string $type = 'all'): array
    {
        $targetDate = date('Y-m-d');
        if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $targetDate = $date;
        }

        $type = $type ?: 'all';

        $whereClause = "DATE(al.horario_entrada) = ?";
        
        if ($period === 'week') {
            $whereClause = "YEARWEEK(al.horario_entrada, 1) = YEARWEEK(?, 1)";
        } elseif ($period === 'month') {
            $whereClause = "YEAR(al.horario_entrada) = YEAR(?)";
        }

        $typeCondition = "";
        if ($type === 'registered') {
            $typeCondition = " AND u.status = 'ativo'";
        } elseif ($type === 'unregistered') {
            $typeCondition = " AND u.status = 'inativo'";
        }

        // 1. Total de acessos no período (Prepared Statement)
        $sqlTotal = "SELECT COUNT(*) as total 
                     FROM acessos_log al 
                     JOIN usuarios u ON al.usuario_id = u.id 
                     WHERE {$whereClause} {$typeCondition}";
                     
        $stmtTotal = $this->db->prepare($sqlTotal);
        $totalAcessos = 0;
        if ($stmtTotal) {
            $stmtTotal->bind_param("s", $targetDate);
            $stmtTotal->execute();
            $resTotal = $stmtTotal->get_result();
            $totalAcessos = ($resTotal && $row = $resTotal->fetch_assoc()) ? (int)$row['total'] : 0;
            $stmtTotal->close();
        }

        // 2. Fluxo horário / período
        $flowData = [];

        if ($period === 'today') {
            $sqlFlow = "SELECT HOUR(al.horario_entrada) as hour, COUNT(*) as count 
                        FROM acessos_log al 
                        JOIN usuarios u ON al.usuario_id = u.id
                        WHERE DATE(al.horario_entrada) = ? {$typeCondition}
                        GROUP BY HOUR(al.horario_entrada) 
                        ORDER BY hour ASC";
                        
            $stmtFlow = $this->db->prepare($sqlFlow);
            $rawHours = [];
            $minHour = 8;
            $maxHour = 17;

            if ($stmtFlow) {
                $stmtFlow->bind_param("s", $targetDate);
                $stmtFlow->execute();
                $resFlow = $stmtFlow->get_result();
                
                if ($resFlow && $resFlow->num_rows > 0) {
                    $first = true;
                    while ($row = $resFlow->fetch_assoc()) {
                        $hour = (int)$row['hour'];
                        $count = (int)$row['count'];
                        $rawHours[$hour] = $count;

                        if ($first) {
                            $minHour = $hour;
                            $maxHour = $hour;
                            $first = false;
                        } else {
                            if ($hour < $minHour) $minHour = $hour;
                            if ($hour > $maxHour) $maxHour = $hour;
                        }
                    }
                }
                $stmtFlow->close();
            }

            $hours = [];
            for ($h = $minHour; $h <= $maxHour; $h++) {
                $hours[$h] = $rawHours[$h] ?? 0;
            }

            foreach ($hours as $hour => $count) {
                $flowData[] = [
                    'label' => sprintf('%02dh', $hour),
                    'count' => $count
                ];
            }
        } elseif ($period === 'week') {
            $dayLabels = ['seg', 'ter', 'qua', 'qui', 'sex', 'sáb', 'dom'];
            $prefilled = array_fill_keys($dayLabels, 0);

            $sqlFlow = "SELECT DAYOFWEEK(al.horario_entrada) as day_num, COUNT(*) as count 
                        FROM acessos_log al 
                        JOIN usuarios u ON al.usuario_id = u.id
                        WHERE {$whereClause} {$typeCondition}
                        GROUP BY DAYOFWEEK(al.horario_entrada)";
                        
            $stmtFlow = $this->db->prepare($sqlFlow);
            
            if ($stmtFlow) {
                $stmtFlow->bind_param("s", $targetDate);
                $stmtFlow->execute();
                $resFlow = $stmtFlow->get_result();

                $dayMap = [
                    2 => 'seg',
                    3 => 'ter',
                    4 => 'qua',
                    5 => 'qui',
                    6 => 'sex',
                    7 => 'sáb',
                    1 => 'dom'
                ];

                if ($resFlow) {
                    while ($row = $resFlow->fetch_assoc()) {
                        $dayNum = (int)$row['day_num'];
                        $label = $dayMap[$dayNum] ?? null;
                        if ($label && isset($prefilled[$label])) {
                            $prefilled[$label] = (int)$row['count'];
                        }
                    }
                }
                $stmtFlow->close();
            }

            foreach ($prefilled as $label => $count) {
                $flowData[] = [
                    'label' => $label,
                    'count' => $count
                ];
            }
        } else { // month
            $monthLabels = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
            $prefilled = array_fill_keys($monthLabels, 0);

            $sqlFlow = "SELECT MONTH(al.horario_entrada) as month_num, COUNT(*) as count 
                        FROM acessos_log al 
                        JOIN usuarios u ON al.usuario_id = u.id
                        WHERE {$whereClause} {$typeCondition}
                        GROUP BY MONTH(al.horario_entrada)";
                        
            $stmtFlow = $this->db->prepare($sqlFlow);
            
            if ($stmtFlow) {
                $stmtFlow->bind_param("s", $targetDate);
                $stmtFlow->execute();
                $resFlow = $stmtFlow->get_result();

                $monthMap = [
                    1 => 'jan',
                    2 => 'fev',
                    3 => 'mar',
                    4 => 'abr',
                    5 => 'mai',
                    6 => 'jun',
                    7 => 'jul',
                    8 => 'ago',
                    9 => 'set',
                    10 => 'out',
                    11 => 'nov',
                    12 => 'dez'
                ];

                if ($resFlow) {
                    while ($row = $resFlow->fetch_assoc()) {
                        $monthNum = (int)$row['month_num'];
                        $label = $monthMap[$monthNum] ?? null;
                        if ($label && isset($prefilled[$label])) {
                            $prefilled[$label] = (int)$row['count'];
                        }
                    }
                }
                $stmtFlow->close();
            }

            foreach ($prefilled as $label => $count) {
                $flowData[] = [
                    'label' => $label,
                    'count' => $count
                ];
            }
        }

        return [
            'total_acessos' => $totalAcessos,
            'flow_data' => $flowData,
            'period' => $period,
            'date' => $targetDate,
            'type' => $type
        ];
    }

    public function getHistory(int $limit = 200, string $period = 'all', ?string $date = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $whereClause = "";
        $params = [];
        $types = "";

        if ($startDate && $endDate) {
            $whereClause = "WHERE DATE(al.horario_entrada) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        } elseif ($period === 'today') {
            $refDate = $date && !empty($date) ? $date : date('Y-m-d');
            $whereClause = "WHERE DATE(al.horario_entrada) = ?";
            $params[] = $refDate;
            $types .= "s";
        } elseif ($period === 'week') {
            $refDate = $date && !empty($date) ? $date : date('Y-m-d');
            $whereClause = "WHERE YEARWEEK(al.horario_entrada, 1) = YEARWEEK(?, 1)";
            $params[] = $refDate;
            $types .= "s";
        } elseif ($period === 'month') {
            $refDate = $date && !empty($date) ? $date : date('Y-m-d');
            $whereClause = "WHERE MONTH(al.horario_entrada) = MONTH(?) AND YEAR(al.horario_entrada) = YEAR(?)";
            $params[] = $refDate;
            $params[] = $refDate;
            $types .= "ss";
        }

        $params[] = $limit;
        $types   .= "i";

        $sql = "SELECT al.id, u.nome, u.turma, al.horario_entrada, al.acao, al.operador 
                FROM acessos_log al
                JOIN usuarios u ON al.usuario_id = u.id
                {$whereClause}
                ORDER BY al.horario_entrada DESC LIMIT ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    public function getTotalCount(): int
    {
        $res = $this->db->query("SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'ativo'");
        return ($res && $row = $res->fetch_assoc()) ? (int)$row['total'] : 0;
    }

    public function getClassDistribution(): array
    {
        $sql = "SELECT turma, COUNT(*) as count FROM {$this->table} WHERE status = 'ativo' GROUP BY turma ORDER BY count DESC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function listInactive(): array
    {
        $sql = "SELECT id, nome, turma, criado_at, rosto_cadastrado_at, status,
                       face_descriptor, face_landmarks
                FROM {$this->table} 
                WHERE status = 'inativo'
                ORDER BY nome ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    public function activate(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'ativo' WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getAllWithDescriptors(): array
    {
        $sql = "SELECT id, nome, status, face_descriptor, face_landmarks 
                FROM {$this->table} 
                WHERE face_descriptor IS NOT NULL AND face_descriptor <> ''";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    public function updateBiometrics(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET 
                face_descriptor = ?, 
                face_landmarks = ?, 
                foto_frontal = ?, 
                foto_esquerda = ?, 
                foto_direita = ?, 
                rosto_cadastrado_at = ? 
                WHERE id = ?";
                
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        
        $stmt->bind_param("ssssssi", 
            $data['face_descriptor'], 
            $data['face_landmarks'], 
            $data['foto_frontal'], 
            $data['foto_esquerda'], 
            $data['foto_direita'], 
            $data['rosto_cadastrado_at'], 
            $id
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}
