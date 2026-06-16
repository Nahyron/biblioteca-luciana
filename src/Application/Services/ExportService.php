<?php

namespace App\Application\Services;

use mysqli;
use Exception;

/**
 * Serviço de Exportação de Dados.
 * Camada de Aplicação: Contém a lógica de formatação de relatórios.
 */
class ExportService
{
    public function __construct(private mysqli $db) {}

    public function generateAccessXls(?string $period = 'all', ?string $targetDate = null, ?string $startDate = null, ?string $endDate = null, ?string $turmaName = null): void
    {
        $whereClause = "";
        $params = [];
        $types = "";
        $refDate = $targetDate && !empty($targetDate) ? $targetDate : date('Y-m-d');

        if ($startDate && $endDate) {
            $whereClause = "WHERE DATE(al.horario_entrada) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        } elseif ($period === 'today') {
            $whereClause = "WHERE DATE(al.horario_entrada) = ?";
            $params[] = $refDate;
            $types .= "s";
        } elseif ($period === 'week') {
            $whereClause = "WHERE YEARWEEK(al.horario_entrada, 1) = YEARWEEK(?, 1)";
            $params[] = $refDate;
            $types .= "s";
        } elseif ($period === 'month') {
            $whereClause = "WHERE MONTH(al.horario_entrada) = MONTH(?) AND YEAR(al.horario_entrada) = YEAR(?)";
            $params[] = $refDate;
            $params[] = $refDate;
            $types .= "ss";
        }

        if ($turmaName && !empty(trim($turmaName))) {
            if (empty($whereClause)) {
                $whereClause = "WHERE u.turma = ?";
            } else {
                $whereClause .= " AND u.turma = ?";
            }
            $params[] = trim($turmaName);
            $types .= "s";
        }

        $sql = "SELECT al.id as log_id, u.nome, u.turma, al.horario_entrada, al.acao, al.operador 
                FROM acessos_log al
                JOIN usuarios u ON al.usuario_id = u.id
                {$whereClause}
                ORDER BY al.horario_entrada DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new Exception("Erro preparo exportação: " . $this->db->error);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) throw new Exception("Erro consulta exportação: " . $this->db->error);

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $filename = 'relatorio_acessos_' . date('Y-m-d_H-i') . '.xls';
        $dataEmissao = date('d/m/Y \à\s H:i');
        $totalRegistros = count($rows);

        $periodLabels = [
            'today' => 'Hoje',
            'week' => 'Esta Semana',
            'month' => 'Este Mês',
            'all' => 'Todos os Registros'
        ];
        $periodLabel = $periodLabels[$period] ?? 'Personalizado';
        if ($startDate && $endDate) {
            $periodLabel = "Período: " . date('d/m/Y', strtotime($startDate)) . " até " . date('d/m/Y', strtotime($endDate));
        }
        if ($turmaName && !empty(trim($turmaName))) {
            $periodLabel .= " | Turma: " . htmlspecialchars($turmaName);
        }

        $html = '<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 11pt; }
    .header-title {
        background-color: #D2232A;
        color: #FFFFFF;
        font-size: 16pt;
        font-weight: bold;
        text-align: center;
        padding: 10pt;
        letter-spacing: 1pt;
    }
    .header-subtitle {
        background-color: #1a1a2e;
        color: #FFFFFF;
        font-size: 9pt;
        text-align: center;
        padding: 4pt;
    }
    .meta-row td {
        background-color: #f5f5f5;
        color: #555555;
        font-size: 9pt;
        padding: 4pt 8pt;
        border-bottom: 1px solid #dddddd;
    }
    .meta-label { font-weight: bold; color: #D2232A; }
    .spacer td { height: 10pt; }
    .col-header td {
        background-color: #D2232A;
        color: #FFFFFF;
        font-weight: bold;
        font-size: 10pt;
        text-align: center;
        padding: 7pt 10pt;
        border: 1px solid #b01c22;
    }
    .row-even td {
        background-color: #FFFFFF;
        color: #333333;
        font-size: 10pt;
        padding: 5pt 10pt;
        border: 1px solid #eeeeee;
        vertical-align: middle;
    }
    .row-odd td {
        background-color: #FFF5F5;
        color: #333333;
        font-size: 10pt;
        padding: 5pt 10pt;
        border: 1px solid #eeeeee;
        vertical-align: middle;
    }
    .footer-row td {
        background-color: #1a1a2e;
        color: #aaaaaa;
        font-size: 8pt;
        text-align: center;
        padding: 5pt;
    }
    .total-row td {
        background-color: #fff0f0;
        color: #D2232A;
        font-weight: bold;
        font-size: 10pt;
        padding: 5pt 10pt;
        border-top: 2px solid #D2232A;
    }
</style>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
    <tr class="header-title"><td colspan="5">SISTEMA BIBLIOTECA — RELATÓRIO DE ACESSOS</td></tr>
    <tr class="header-subtitle"><td colspan="5">Controle de Acesso por Visão Computacional</td></tr>
    <tr class="meta-row">
        <td><span class="meta-label">Data de Emissão:</span></td>
        <td>' . htmlspecialchars($dataEmissao) . '</td>
        <td><span class="meta-label">Período:</span></td>
        <td>' . htmlspecialchars($periodLabel) . ' (' . htmlspecialchars($refDate) . ')</td>
        <td><span class="meta-label">Total Acessos:</span></td>
        <td>' . $totalRegistros . '</td>
    </tr>
    <tr class="spacer"><td colspan="5"></td></tr>
    <tr class="col-header">
        <td>ID Log</td>
        <td>Nome do Aluno</td>
        <td>Turma</td>
        <td>Data de Entrada</td>
        <td>Hora de Entrada</td>
    </tr>';

        foreach ($rows as $i => $data) {
            $rowClass = ($i % 2 === 0) ? 'row-even' : 'row-odd';
            $timestamp = strtotime($data['horario_entrada']);
            $entradaData = date('d/m/Y', $timestamp);
            $entradaHora = date('H:i:s', $timestamp);

            $nomeExibicao = htmlspecialchars($data['nome']);
            $horaExibicao = htmlspecialchars($entradaHora);

            if (($data['acao'] ?? 'entrada') === 'desativacao') {
                $nomeExibicao .= ' (DESATIVADO)';
                $horaExibicao .= ' [Desativado por: ' . htmlspecialchars($data['operador'] ?? 'Sistema') . ']';
            } elseif (($data['acao'] ?? 'entrada') === 'ativacao') {
                $nomeExibicao .= ' (REATIVADO)';
                $horaExibicao .= ' [Reativado por: ' . htmlspecialchars($data['operador'] ?? 'Sistema') . ']';
            }

            $html .= '<tr class="' . $rowClass . '">
                <td align="center">#' . htmlspecialchars($data['log_id']) . '</td>
                <td>' . $nomeExibicao . '</td>
                <td align="center">' . htmlspecialchars($data['turma'] ?? 'Sem Turma') . '</td>
                <td align="center">' . htmlspecialchars($entradaData) . '</td>
                <td align="center">' . $horaExibicao . '</td>
            </tr>';
        }

        $html .= '
    <tr class="total-row">
        <td colspan="5">Total de registros exportados: ' . $totalRegistros . '</td>
    </tr>
    <tr class="footer-row">
        <td colspan="5">Documento gerado automaticamente pelo Sistema Biblioteca em ' . htmlspecialchars($dataEmissao) . '</td>
    </tr>
</table>
</body>
</html>';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        echo $html;
        exit;
    }

    public function generateStudentsCsv(): void
    {
        $sql = "SELECT id, nome, turma, rosto_cadastrado_at, ultima_entrada_at FROM usuarios WHERE status = 'ativo' ORDER BY nome ASC";
        $result = $this->db->query($sql);
        if (!$result) throw new Exception("Erro consulta exportação alunos.");

        $this->outputCsv('relatorio_alunos_' . date('Y-m-d_H-i') . '.csv', [
            ['ID', 'Nome', 'Turma', 'Data de Cadastro', 'Data Último Acesso', 'Hora Último Acesso'],
            function() use ($result) {
                $rows = [];
                while ($data = $result->fetch_assoc()) {
                    $cadastro = $data['rosto_cadastrado_at'] ? sprintf('="%s"', date('d/m/Y', strtotime($data['rosto_cadastrado_at']))) : 'N/A';
                    
                    $ultimoAcessoData = 'Nunca';
                    $ultimoAcessoHora = '-';
                    if ($data['ultima_entrada_at']) {
                        $ts = strtotime($data['ultima_entrada_at']);
                        $ultimoAcessoData = sprintf('="%s"', date('d/m/Y', $ts));
                        $ultimoAcessoHora = date('H:i:s', $ts);
                    }

                    $rows[] = [
                        $data['id'],
                        $data['nome'],
                        $data['turma'] ?? 'Sem Turma',
                        $cadastro,
                        $ultimoAcessoData,
                        $ultimoAcessoHora
                    ];
                }
                return $rows;
            }
        ]);
    }

    /**
     * Gera planilha XLS estilizada dos alunos (HTML table com mime XLSX).
     * Visual customizado com identidade do sistema (vermelho/branco).
     */
    public function generateStudentsXls(): void
    {
        $sql = "SELECT id, nome, turma, rosto_cadastrado_at, ultima_entrada_at FROM usuarios WHERE status = 'ativo' ORDER BY nome ASC";
        $result = $this->db->query($sql);
        if (!$result) throw new Exception("Erro na consulta de alunos para exportação XLS.");

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $filename = 'alunos_' . date('Y-m-d_H-i') . '.xls';
        $dataEmissao = date('d/m/Y \à\s H:i');
        $totalAlunos = count($rows);

        $html = '<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 11pt; }
    .header-title {
        background-color: #D2232A;
        color: #FFFFFF;
        font-size: 16pt;
        font-weight: bold;
        text-align: center;
        padding: 10pt;
        letter-spacing: 1pt;
    }
    .header-subtitle {
        background-color: #1a1a2e;
        color: #FFFFFF;
        font-size: 9pt;
        text-align: center;
        padding: 4pt;
    }
    .meta-row td {
        background-color: #f5f5f5;
        color: #555555;
        font-size: 9pt;
        padding: 4pt 8pt;
        border-bottom: 1px solid #dddddd;
    }
    .meta-label { font-weight: bold; color: #D2232A; }
    .spacer td { height: 10pt; }
    .col-header td {
        background-color: #D2232A;
        color: #FFFFFF;
        font-weight: bold;
        font-size: 10pt;
        text-align: center;
        padding: 7pt 10pt;
        border: 1px solid #b01c22;
    }
    .row-even td {
        background-color: #FFFFFF;
        color: #333333;
        font-size: 10pt;
        padding: 5pt 10pt;
        border: 1px solid #eeeeee;
        vertical-align: middle;
    }
    .row-odd td {
        background-color: #FFF5F5;
        color: #333333;
        font-size: 10pt;
        padding: 5pt 10pt;
        border: 1px solid #eeeeee;
        vertical-align: middle;
    }
    .status-active {
        color: #1a7a1a;
        font-weight: bold;
    }
    .status-never {
        color: #999999;
        font-style: italic;
    }
    .footer-row td {
        background-color: #1a1a2e;
        color: #aaaaaa;
        font-size: 8pt;
        text-align: center;
        padding: 5pt;
    }
    .total-row td {
        background-color: #fff0f0;
        color: #D2232A;
        font-weight: bold;
        font-size: 10pt;
        padding: 5pt 10pt;
        border-top: 2px solid #D2232A;
    }
</style>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
    <tr class="header-title"><td colspan="6">SISTEMA BIBLIOTECA — RELATÓRIO DE ALUNOS</td></tr>
    <tr class="header-subtitle"><td colspan="6">Controle de Acesso por Visão Computacional</td></tr>
    <tr class="meta-row">
        <td><span class="meta-label">Data de Emissão:</span></td>
        <td>' . htmlspecialchars($dataEmissao) . '</td>
        <td><span class="meta-label">Total de Alunos:</span></td>
        <td>' . $totalAlunos . '</td>
        <td colspan="2"></td>
    </tr>
    <tr class="spacer"><td colspan="6"></td></tr>
    <tr class="col-header">
        <td>ID</td>
        <td>Nome do Aluno</td>
        <td>Turma</td>
        <td>Data de Cadastro</td>
        <td>Último Acesso</td>
        <td>Hora do Acesso</td>
    </tr>';

        foreach ($rows as $i => $data) {
            $rowClass = ($i % 2 === 0) ? 'row-even' : 'row-odd';
            $cadastro = $data['rosto_cadastrado_at']
                ? date('d/m/Y', strtotime($data['rosto_cadastrado_at']))
                : 'N/A';

            $ultimoAcessoData = '<span class="status-never">Nunca</span>';
            $ultimoAcessoHora = '<span class="status-never">—</span>';

            if ($data['ultima_entrada_at']) {
                $ts = strtotime($data['ultima_entrada_at']);
                $ultimoAcessoData = '<span class="status-active">' . date('d/m/Y', $ts) . '</span>';
                $ultimoAcessoHora = date('H:i:s', $ts);
            }

            $html .= '<tr class="' . $rowClass . '">
                <td align="center">' . htmlspecialchars($data['id']) . '</td>
                <td>' . htmlspecialchars($data['nome']) . '</td>
                <td align="center">' . htmlspecialchars($data['turma'] ?? 'Sem Turma') . '</td>
                <td align="center">' . htmlspecialchars($cadastro) . '</td>
                <td align="center">' . $ultimoAcessoData . '</td>
                <td align="center">' . $ultimoAcessoHora . '</td>
            </tr>';
        }

        $html .= '
    <tr class="total-row">
        <td colspan="6">Total de alunos cadastrados: ' . $totalAlunos . '</td>
    </tr>
    <tr class="footer-row">
        <td colspan="6">Documento gerado automaticamente pelo Sistema Biblioteca em ' . htmlspecialchars($dataEmissao) . '</td>
    </tr>
</table>
</body>
</html>';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        echo $html;
        exit;
    }

    private function outputCsv(string $filename, array $content): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($output, $content[0], ';');
        $rows = $content[1]();
        foreach ($rows as $row) {
            fputcsv($output, $row, ';');
        }
        fclose($output);
        exit;
    }
}
