<?php

namespace App\Http\Requests;

use App\Services\ReportSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a report request before it reaches the query builder.
 *
 * MainReportsController used to hand `$request->all()` straight to
 * ReportQueryBuilder, which reads `report_type`, `date_range.type` and
 * `column_set` unguarded. An incomplete body therefore failed as an
 * undefined-key error and surfaced as a 500 — a malformed request answered as a
 * server fault, with nothing the UI could show the user.
 *
 * The route is already restricted to admins, so this is about correctness and
 * error reporting rather than access control. It does still keep unvalidated
 * client strings away from SQL assembly, which the filter whitelist in
 * ReportQueryBuilder handles separately.
 */
class ReportRequest extends FormRequest
{
    /** Report types ReportSchema knows how to build. */
    public const TYPES = ['booking', 'payment', 'combined'];

    /** Supported ways of bounding a report by date. */
    public const DATE_TYPES = ['monthly', 'yearly', 'weekly', 'range'];

    public function authorize(): bool
    {
        // Access is enforced by the staff.role middleware on the route.
        return true;
    }

    /**
     * Fill in the column set from the report type when the client omits it, so
     * a valid-but-terse request does not fall through to the mapper's
     * `bookings.*` catch-all.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('column_set')) {
            return;
        }

        if (! in_array($this->input('report_type'), self::TYPES, true)) {
            return;
        }

        $this->merge([
            'column_set' => ReportSchema::get($this->input('report_type'))['allowed_columns'],
        ]);
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in(self::TYPES)],
            'column_set'  => ['required', 'string'],

            'date_range'       => ['required', 'array'],
            'date_range.type'  => ['required', Rule::in(self::DATE_TYPES)],

            // A month is YYYY-MM; a year is four digits. Both are read apart by
            // the query builder, so the shape has to be right before it gets there.
            'date_range.value' => ['required'],

            'filters'    => ['nullable', 'array'],
            'match_type' => ['nullable', Rule::in(['AND', 'OR'])],
            'format'     => ['nullable', Rule::in(['xlsx', 'csv'])],
        ];
    }

    /**
     * Shape checks for `date_range.value`, which varies by `date_range.type`.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type  = $this->input('date_range.type');
            $value = $this->input('date_range.value');

            if ($type === 'monthly' && ! preg_match('/^\d{4}-\d{1,2}$/', (string) $value)) {
                $validator->errors()->add('date_range.value', 'Expected a month in YYYY-MM format.');
            }

            if ($type === 'yearly' && ! preg_match('/^\d{4}$/', (string) $value)) {
                $validator->errors()->add('date_range.value', 'Expected a four-digit year.');
            }

            if ($type === 'range') {
                foreach (['from', 'to'] as $bound) {
                    if (empty($value[$bound]) || strtotime((string) $value[$bound]) === false) {
                        $validator->errors()->add(
                            "date_range.value.{$bound}",
                            "Expected a valid {$bound} date.",
                        );
                    }
                }
            }

            if ($type === 'weekly') {
                if (! is_array($value) || count($value) !== 2) {
                    $validator->errors()->add(
                        'date_range.value',
                        'Expected a [start, end] pair.',
                    );

                    return;
                }

                foreach ($value as $i => $bound) {
                    if (strtotime((string) $bound) === false) {
                        $validator->errors()->add("date_range.value.{$i}", 'Expected a valid date.');
                    }
                }
            }
        });
    }
}
