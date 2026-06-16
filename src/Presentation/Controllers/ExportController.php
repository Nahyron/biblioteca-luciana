<?php

namespace App\Presentation\Controllers;

use App\Application\Services\ExportService;

/**
 * Controller de Exportação.
 * Camada de Apresentação: Processa requisições de download.
 */
class ExportController
{
    public function __construct(private ExportService $service) {}

    public function exportExcel(string $period, ?string $date = null, ?string $startDate = null, ?string $endDate = null, ?string $turmaName = null): void
    {
        $this->service->generateAccessXls($period, $date, $startDate, $endDate, $turmaName);
    }

    public function exportStudents(): void
    {
        $this->service->generateStudentsCsv();
    }

    public function exportStudentsXls(): void
    {
        $this->service->generateStudentsXls();
    }
}
