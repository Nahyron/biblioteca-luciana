<?php

namespace App\Application\Services;

use App\Domain\Repositories\StudentRepositoryInterface;

/**
 * Serviço de Aplicação para Alunos e Acessos.
 * Centraliza a lógica de negócio separando-a do controller HTTP.
 */
class StudentService
{
    public function __construct(private StudentRepositoryInterface $repository) {}

    public function listAll(): array
    {
        return $this->repository->getAll();
    }

    public function register(array $data): array
    {
        if (empty($data['nome']) || empty($data['turma'])) {
            return ['success' => false, 'message' => 'Dados obrigatórios ausentes.'];
        }

        // Validação e higienização estrita (letras, acentos, números, espaços, hífens e ordinais º/ª)
        $nomeLimpo = trim($data['nome']);
        $turmaLimpa = trim($data['turma']);

        if (!preg_match('/^[\p{L}\p{N}\s\-ºª]+$/u', $nomeLimpo) || !preg_match('/^[\p{L}\p{N}\s\-ºª]+$/u', $turmaLimpa)) {
            return ['success' => false, 'message' => 'Caracteres especiais não permitidos nos campos de cadastro.'];
        }

        $data['nome'] = preg_replace('/[^\p{L}\p{N}\s\-ºª]/u', '', $nomeLimpo);
        $data['turma'] = preg_replace('/[^\p{L}\p{N}\s\-ºª]/u', '', $turmaLimpa);



        // Processa as imagens recebidas no payload biométrico para não lotar o BD
        if (!empty($data['face_landmarks'])) {
            $faceDataInfo = json_decode($data['face_landmarks'], true);
            if (isset($faceDataInfo['images']) && is_array($faceDataInfo['images'])) {
                $savedPaths = [];
                $uploadDir = PUBLIC_PATH . '/assets/uploads/faces';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($faceDataInfo['images'] as $idx => $base64Str) {
                    if (empty($base64Str)) continue;
                    // Extrai dados da img base64 (removendo `data:image/jpeg;base64,`)
                    $parts = explode(',', $base64Str);
                    if (count($parts) === 2) {
                        $imageData = base64_decode($parts[1]);
                        $filename = 'face_' . time() . '_' . rand(100, 999) . "_angle{$idx}.jpg";
                        $fullPath = $uploadDir . '/' . $filename;
                        if (file_put_contents($fullPath, $imageData)) {
                            // Armazenamos no BD apenas o caminho relativo para a imagem
                            $savedPaths[] = "assets/uploads/faces/{$filename}";
                        }
                    }
                }
                
                $data['foto_frontal'] = $savedPaths[0] ?? null;
                $data['foto_esquerda'] = $savedPaths[1] ?? null;
                $data['foto_direita'] = $savedPaths[2] ?? null;
                
                // Limpa as imagens do JSON pra evitar inchaço redundante
                unset($faceDataInfo['images']);
                $data['face_landmarks'] = json_encode($faceDataInfo);
            }
        }

        $success = $this->repository->create($data);
        return [
            'success' => $success,
            'message' => $success ? 'Usuário cadastrado com sucesso!' : 'Erro ao cadastrar usuário.'
        ];
    }

    public function recordAccess(int $studentId): bool
    {
        return $this->repository->logAccess($studentId);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function deleteHistory(int $logId): bool
    {
        return $this->repository->deleteHistory($logId);
    }

    public function updateName(int $id, string $newName): bool
    {
        $newName = trim($newName);
        if (!preg_match('/^[\p{L}\p{N}\s\-ºª]+$/u', $newName)) {
            return false;
        }
        $newNameClean = preg_replace('/[^\p{L}\p{N}\s\-ºª]/u', '', $newName);
        return $this->repository->update($id, ['nome' => $newNameClean]);
    }

    public function updateHistory(int $logId, string $newDate): bool
    {
        return $this->repository->updateHistory($logId, $newDate);
    }

    public function updateClass(int $id, string $className): bool
    {
        $className = trim($className);
        if (!preg_match('/^[\p{L}\p{N}\s\-ºª]+$/u', $className)) {
            return false;
        }
        $classNameClean = preg_replace('/[^\p{L}\p{N}\s\-ºª]/u', '', $className);
        return $this->repository->updateClass($id, $classNameClean);
    }

    public function getDashboardStats(string $period = 'today', ?string $date = null, ?string $type = 'all'): array
    {
        return $this->repository->getDashboardStats($period, $date, $type);
    }

    public function listHistory(string $period = 'all', ?string $date = null): array
    {
        return $this->repository->getHistory(200, $period, $date);
    }

    public function listInactive(): array
    {
        return $this->repository->listInactive();
    }

    public function activate(int $id): bool
    {
        return $this->repository->activate($id);
    }

    public function updateBiometrics(int $id, array $data): bool
    {
        if ($id <= 0) return false;

        $fotoFrontal = null;
        $fotoEsquerda = null;
        $fotoDireita = null;
        $landmarks = $data['face_landmarks'] ?? null;

        if (!empty($landmarks)) {
            $faceDataInfo = json_decode($landmarks, true);
            if (isset($faceDataInfo['images']) && is_array($faceDataInfo['images'])) {
                $savedPaths = [];
                $uploadDir = PUBLIC_PATH . '/assets/uploads/faces';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($faceDataInfo['images'] as $idx => $base64Str) {
                    if (empty($base64Str)) continue;
                    $parts = explode(',', $base64Str);
                    if (count($parts) === 2) {
                        $imageData = base64_decode($parts[1]);
                        $filename = 'face_' . time() . '_' . rand(100, 999) . "_angle{$idx}.jpg";
                        $fullPath = $uploadDir . '/' . $filename;
                        if (file_put_contents($fullPath, $imageData)) {
                            $savedPaths[] = "assets/uploads/faces/{$filename}";
                        }
                    }
                }
                
                $fotoFrontal = $savedPaths[0] ?? null;
                $fotoEsquerda = $savedPaths[1] ?? null;
                $fotoDireita = $savedPaths[2] ?? null;
                
                unset($faceDataInfo['images']);
                $landmarks = json_encode($faceDataInfo);
            }
        }

        $isRemoving = empty($data['face_descriptor']);
        $updateData = [
            'face_descriptor' => $data['face_descriptor'] ?? null,
            'face_landmarks' => $landmarks,
            'foto_frontal' => $fotoFrontal,
            'foto_esquerda' => $fotoEsquerda,
            'foto_direita' => $fotoDireita,
            'rosto_cadastrado_at' => $isRemoving ? null : date('Y-m-d H:i:s')
        ];

        return $this->repository->updateBiometrics($id, $updateData);
    }

    public function importFromExcel(string $filePath, string $className): array
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet   = $spreadsheet->getActiveSheet();
            $rows        = $worksheet->toArray();
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erro ao processar a planilha: ' . $e->getMessage()
            ];
        }

        if (empty($rows)) {
            return ['success' => false, 'message' => 'A planilha está vazia ou não pôde ser lida.'];
        }

        // --- Busca inteligente da coluna de nomes ---
        $header       = $rows[0];
        $nomeColIndex = -1;
        foreach ($header as $idx => $colName) {
            if ($colName === null) continue;
            $clean = strtolower(trim($colName));
            if (
                $clean === 'nome'      || $clean === 'name'      ||
                $clean === 'aluno'     || $clean === 'student'   ||
                str_contains($clean, 'nome')      ||
                str_contains($clean, 'name')      ||
                str_contains($clean, 'aluno')     ||
                str_contains($clean, 'estudante') ||
                str_contains($clean, 'student')
            ) {
                $nomeColIndex = $idx;
                break;
            }
        }

        // Detecta se a linha 0 é um cabeçalho ou já são dados
        $headerIsRow0 = ($nomeColIndex !== -1);
        if (!$headerIsRow0) {
            $nomeColIndex   = 0; // fallback: assume coluna A
            $firstCellClean = strtolower(trim($rows[0][0] ?? ''));
            if (in_array($firstCellClean, ['nome', 'aluno', 'student', 'estudante', 'name'])) {
                $headerIsRow0 = true;
            }
        }
        $startIndex = $headerIsRow0 ? 1 : 0;

        // --- Carrega todos os alunos ativos do sistema ---
        $existing = $this->repository->getAll();

        // Nomes já cadastrados NESTA turma (para impedir duplicação)
        $namesInClass = [];
        // Nomes cadastrados em OUTRAS turmas do sistema (para aviso informativo)
        $namesInOtherClasses = [];

        foreach ($existing as $st) {
            $nomeLow = strtolower(trim($st['nome']));
            if ($st['turma'] === $className) {
                $namesInClass[] = $nomeLow;
            } else {
                $namesInOtherClasses[$nomeLow] = $st['nome']; // guarda o nome original
            }
        }

        $imported          = 0;
        $errors            = 0;
        $duplicateNames    = [];   // já existem nesta turma → ignorados silenciosamente (sem duplicar)
        $inOtherClassNames = [];   // existem em outras turmas → aviso informativo

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row  = $rows[$i];
            if (!isset($row[$nomeColIndex])) continue;
            $nome = trim($row[$nomeColIndex]);
            if (empty($nome)) continue;

            $nomeLow = strtolower($nome);

            // Já existe nesta turma → pula sem duplicar, registra para aviso
            if (in_array($nomeLow, $namesInClass)) {
                if (!in_array($nome, $duplicateNames)) {
                    $duplicateNames[] = $nome;
                }
                continue;
            }

            // Existe em outra turma → registra aviso, mas importa normalmente para esta turma
            if (isset($namesInOtherClasses[$nomeLow])) {
                $inOtherClassNames[] = $nome;
            }

            $studentData = [
                'nome'            => $nome,
                'turma'           => $className,
                'face_descriptor' => null,
                'face_landmarks'  => null,
                'foto_frontal'    => null,
                'foto_esquerda'   => null,
                'foto_direita'    => null
            ];

            if ($this->repository->create($studentData)) {
                $imported++;
                $namesInClass[] = $nomeLow; // atualiza para evitar duplicidade dentro da própria planilha
            } else {
                $errors++;
            }
        }

        return [
            'success'              => true,
            'message'              => 'Importação processada com sucesso.',
            'imported'             => $imported,
            'errors'               => $errors,
            'duplicates'           => count($duplicateNames),
            'duplicate_names'      => $duplicateNames,       // já existiam nesta turma
            'in_other_classes'     => count($inOtherClassNames),
            'in_other_class_names' => $inOtherClassNames,   // existem em outras turmas (aviso)
        ];
    }


}
