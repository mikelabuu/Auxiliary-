<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] }}</title>
    {{--
        Written for dompdf, not for the browser. It supports roughly CSS 2.1 —
        no flexbox, no grid, no custom properties — so the layout here is plain
        block and table markup and the colours are literal hex rather than the
        admin theme's tokens. Reusing the screen table's classes would render
        as an unstyled column of text.

        DejaVu Sans is the one bundled font with the glyph coverage for the
        peso sign; the default Helvetica renders it as a box.
    --}}
    <style>
        @page { margin: 92px 28px 56px 28px; }

        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #1c1917; margin: 0; }

        /* Fixed-position blocks repeat on every page in dompdf, which is how
           a multi-page report keeps its identity and page numbers. */
        .page-header { position: fixed; top: -70px; left: 0; right: 0; height: 62px; }
        .page-footer { position: fixed; bottom: -34px; left: 0; right: 0; height: 24px;
                       font-size: 8px; color: #78716c; border-top: 1px solid #e7e5e4; padding-top: 5px; }

        /* CSS counters, not dompdf's page_script. The script form needs
           enable_php, which is false in config/dompdf.php and would have to be
           turned on globally — allowing arbitrary PHP inside any rendered
           template, including the booking receipt. Counters are handled by the
           layout engine and need no such switch. */
        .pagenum:after { content: counter(page) " of " counter(pages); }

        .brand { font-size: 14px; font-weight: bold; letter-spacing: .3px; }
        .doc-title { font-size: 11px; color: #44403c; margin-top: 1px; }
        .meta { font-size: 8px; color: #78716c; margin-top: 3px; }
        .rule { border-bottom: 1.5px solid #1c1917; margin-top: 6px; }

        .totals { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .totals td { border: 1px solid #e7e5e4; background: #fafaf9; padding: 6px 9px; width: 33%; }
        .totals .t-label { font-size: 7.5px; color: #78716c; text-transform: uppercase; letter-spacing: .6px; }
        .totals .t-value { font-size: 13px; font-weight: bold; padding-top: 2px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #1c1917; color: #fff; font-size: 8px; text-transform: uppercase;
                        letter-spacing: .5px; padding: 6px 7px; text-align: left; }
        table.data td { border-bottom: 1px solid #e7e5e4; padding: 5px 7px; }
        /* thead repeats automatically across page breaks in dompdf, so a
           report longer than one page keeps its column labels. */
        table.data tr:nth-child(even) td { background: #fafaf9; }

        .notice { margin-top: 10px; padding: 7px 9px; border: 1px solid #fbbf24; background: #fffbeb;
                  font-size: 8px; color: #78350f; }
        .empty { padding: 26px; text-align: center; color: #78716c; border: 1px solid #e7e5e4; }
    </style>
</head>
<body>

<div class="page-header">
    <div class="brand">{{ config('app.name') }}</div>
    <div class="doc-title">{{ $meta['title'] }} &mdash; {{ $meta['period'] }}</div>
    <div class="meta">{{ $meta['filters'] }}</div>
    <div class="rule"></div>
</div>

<div class="page-footer">
    Generated {{ now('Asia/Manila')->format('M d, Y \a\t g:i A') }}
    &nbsp;·&nbsp; Page <span class="pagenum"></span>
</div>

@if ($summary)
    <table class="totals">
        <tr>
            @foreach ($summary as $card)
                <td>
                    <div class="t-label">{{ $card['label'] }}</div>
                    <div class="t-value">
                        @if ($card['format'] === 'money')
                            PHP {{ number_format($card['value'], 2) }}
                        @else
                            {{ number_format($card['value']) }}
                        @endif
                    </div>
                </td>
            @endforeach
        </tr>
    </table>
@endif

@if ($rows->isEmpty())
    <div class="empty">No records matched these filters.</div>
@else
    <table class="data">
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ((array) $row as $value)
                        <td>{{ $value === null || $value === '' ? '—' : $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if ($truncated)
    <div class="notice">
        <strong>This report is incomplete.</strong>
        It matched more than {{ number_format($limit) }} records and was cut at that point.
        Narrow the date range, or use the Excel export, which has no such limit.
    </div>
@endif

</body>
</html>
