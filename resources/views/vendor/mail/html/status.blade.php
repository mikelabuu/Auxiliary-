@props(['tone' => 'neutral'])
@php
    $tones = [
        'success' => ['background' => '#EDFDF3', 'border' => '#AAF0C4', 'text' => '#087443'],
        'warning' => ['background' => '#FFFAEB', 'border' => '#FEDF89', 'text' => '#93370D'],
        'danger' => ['background' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#B91C1C'],
        'neutral' => ['background' => '#F6F9F6', 'border' => '#DCE6DE', 'text' => '#39463E'],
    ];

    $palette = $tones[$tone] ?? $tones['neutral'];
@endphp
<table class="mail-status" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="mail-status-cell" bgcolor="{{ $palette['background'] }}" style="background-color: {{ $palette['background'] }}; border: 1px solid {{ $palette['border'] }}; border-radius: 5px; color: {{ $palette['text'] }}; padding: 7px 12px; font-family: Arial, Helvetica, sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.4px; line-height: 1.2; text-transform: uppercase;">
{{ $slot }}
</td>
</tr>
</table>
