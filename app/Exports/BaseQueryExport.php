<?php

namespace App\Exports;

use App\Exports\Concerns\RegistersStandardExportSheet;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Query-based exports: Maatwebsite Excel reads the database in chunks (see config excel.exports.chunk_size),
 * avoiding loading every row into PHP memory at once (unlike {@see BaseExport} + FromCollection).
 */
abstract class BaseQueryExport implements FromQuery, WithHeadings, WithMapping, WithCustomStartCell, WithEvents, ShouldAutoSize, WithCustomChunkSize
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

    public function chunkSize(): int
    {
        return (int) config('excel.exports.chunk_size', 1000);
    }

    public function startCell(): string
    {
        $startRow = ! empty($this->type) ? 5 : 4;

        return "A{$startRow}";
    }

    public function registerEvents(): array
    {
        return $this->registerStandardExportSheetEvents();
    }

    /**
     * @return EloquentBuilder
     */
    abstract public function query();

    abstract public function headings(): array;

    /**
     * @param  mixed  $row
     * @return array<int|string, mixed>
     */
    abstract public function map($row): array;
}
