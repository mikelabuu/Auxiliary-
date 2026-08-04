<?php

namespace App\Exports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * The spreadsheet behind Analytics & Reporting's Export button.
 *
 * WithHeadings is not decoration. The rows arrive as bare stdClass records
 * straight from ReportQueryBuilder's select(), so without a heading row the
 * download opened as five or six unlabelled columns of ids, names, amounts and
 * dates — readable on screen, where the table renders its own <th>, and
 * anonymous the moment it left the browser. Anyone who had to act on the file
 * was left matching columns by eye.
 *
 * The labels are derived from the query's own column aliases rather than
 * hard-coded, so a change to a column set in ReportColumnMapper cannot leave
 * the headings describing the previous one. humanize() in
 * resources/js/pages/admin-reports.js formats the on-screen <th> the same way,
 * which is what keeps the sheet and the screen reading identically.
 */
class GenericReportExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        $first = collect($this->data)->first();

        // An empty result still exports, and a sheet with headings and no rows
        // says "nothing matched" far more clearly than a blank one.
        if (! $first) {
            return [];
        }

        return collect(array_keys((array) $first))
            ->map(fn ($key) => Str::of($key)->replace('_', ' ')->title()->toString())
            ->all();
    }
}
