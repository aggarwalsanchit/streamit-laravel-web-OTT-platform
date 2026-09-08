<?php

namespace App\Exports;

use App\Exports\Concerns\RegistersStandardExportSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

abstract class BaseExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents, ShouldAutoSize
{
    use RegistersStandardExportSheet;

    public array $columns;
    public array $dateRange;
    public $type;
    public string $reportName;

    public function __construct($columns, $dateRange, $type, $reportName = 'Report')
    {
        $this->columns = $columns;
        $this->dateRange = $dateRange;
        $this->type = $type;
        $this->reportName = $reportName;
    }

    public function startCell(): string
    {
        // Start actual data headings after report info
        // If type is available: row 5, if not: row 4
        $startRow = !empty($this->type) ? 5 : 4;
        return "A{$startRow}";
    }

    public function registerEvents(): array
    {
        return $this->registerStandardExportSheetEvents();
    }

    protected function applyDefaultSheetSettings($sheet, $worksheet): bool
    {
        $isRtl = in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur'], true);

        $sheet->getDelegate()->getParent()
            ->getDefaultStyle()
            ->getFont()
            ->setName('DejaVu Sans')
            ->setSize(10);

        if ($isRtl) {
            $worksheet->setRightToLeft(true);
        }

        return $isRtl;
    }

    // Abstract methods that must be implemented by child classes
    abstract public function headings(): array;
    abstract public function collection();
}
