<?php

declare(strict_types=1);

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

final class ExcelChunkReadFilter implements IReadFilter
{
    private int $startRow = 1;

    private int $endRow = 1;

    public function __construct(private readonly int $headerRow = 1) {}

    public function setRows(int $startRow, int $chunkSize): void
    {
        $this->startRow = max($this->headerRow + 1, $startRow);
        $this->endRow = $this->startRow + max(1, $chunkSize) - 1;
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        return $row === $this->headerRow || ($row >= $this->startRow && $row <= $this->endRow);
    }
}
