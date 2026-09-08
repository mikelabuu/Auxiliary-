@props([
    'title' => null,
    'rows' => [],
])
<table class="mail-details" width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#F6F9F6">
<tr>
<td class="mail-details-cell" style="background-color: #F6F9F6; border: 1px solid #DCE6DE; border-top: 3px solid #0F8F51; padding: 21px 23px 20px;">
@if ($title)
<p class="mail-details-eyebrow" style="margin: 0 0 4px; color: #5C7266; font-family: Arial, Helvetica, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.2px; line-height: 1.35; text-transform: uppercase;">Reservation details</p>
<p class="mail-details-title" style="margin: 0 0 12px; color: #14201A; font-family: Georgia, 'Times New Roman', Times, serif; font-size: 18px; font-weight: 700; line-height: 1.35;">{{ $title }}</p>
@endif
<table class="mail-details-table" width="100%" cellpadding="0" cellspacing="0" role="presentation">
@foreach ($rows as $label => $value)
<tr>
<th class="mail-details-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #DCE6DE; padding: 11px 12px 11px 0; color: #5C7266; font-size: 12px; font-weight: 600; line-height: 1.45;">{{ $label }}</th>
<td class="mail-details-value" valign="top" style="border-top: 1px solid #DCE6DE; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1.45; overflow-wrap: anywhere; word-break: break-word;">{{ $value }}</td>
</tr>
@endforeach
</table>
</td>
</tr>
</table>
