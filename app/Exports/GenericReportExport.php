<?php

namespace App\Exports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GenericReportExport extends DefaultValueBinder implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(protected $data, protected array $columns = []) {}

    public function collection()
    {
        return collect($this->data);
    }

    private function keys(): array
    {
        $first = collect($this->data)->first();
        return $first ? array_keys((array) $first) : $this->columns;
    }

    public function headings(): array
    {
        return array_map(fn ($key) => Str::of($key)->replace('_', ' ')->title()->toString(), $this->keys());
    }

    public function bindValue(Cell $cell, $value): bool
    {
        $key = $this->keys()[Coordinate::columnIndexFromString($cell->getColumn()) - 1] ?? '';
        $numeric = in_array($key, ['id', 'expected_guests', 'payable_amount', 'extra_mattress', 'extra_mattress_amount'], true);
        if ($cell->getRow() > 1 && $numeric && is_numeric($value)) {
            $cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);
        } else {
            // Guest-entered text must never become an executable spreadsheet formula.
            $text = (string) ($value ?? '');
            if (preg_match('/^[\\s]*[=+@-]/u', $text)) $text = "'" . $text;
            $cell->setValueExplicit($text, DataType::TYPE_STRING);
        }
        return true;
    }

    public function columnFormats(): array
    {
        $formats = [];
        foreach ($this->keys() as $index => $key) {
            if (in_array($key, ['payable_amount', 'extra_mattress_amount'], true)) {
                $formats[Coordinate::stringFromColumnIndex($index + 1)] = '#,##0.00';
            }
        }
        return $formats;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        $sheet->getRowDimension(1)->setRowHeight(28);
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '14532D']]]];
    }
}
