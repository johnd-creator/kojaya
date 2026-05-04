<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelExportService
{
    public function export(mixed $export, string $fileName): object
    {
        return new class($export, $fileName)
        {
            public function __construct(
                private readonly mixed $export,
                private readonly string $fileName,
            ) {}

            public function download(): BinaryFileResponse
            {
                return Excel::download($this->export, $this->fileName);
            }
        };
    }
}
