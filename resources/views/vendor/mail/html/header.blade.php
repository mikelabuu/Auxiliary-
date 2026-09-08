@props(['url', 'logoSrc'])
<tr>
<td class="header" align="center" style="padding: 30px 12px 0;">
<table class="header-shell" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#0F8F51">
<tr>
<td class="hostel-header-banner" bgcolor="#0F8F51" style="background-color: #0F8F51; padding: 23px 34px 22px;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="hostel-logo-cell" width="72" valign="middle" style="width: 72px; padding-right: 16px;">
<a href="{{ $url }}" style="display: block; color: #FFFFFF; text-decoration: none;">
<img class="hostel-logo" src="{{ $logoSrc }}" width="56" height="56" alt="Farmers Hostel" style="display: block; width: 56px; height: 56px; border: 0; outline: none; text-decoration: none;">
</a>
</td>
<td valign="middle" align="left">
<a href="{{ $url }}" style="display: block; color: #FFFFFF; text-decoration: none;">
<span class="hostel-header-eyebrow" style="display: block; margin-bottom: 4px; color: #D3F8E0; font-family: Arial, Helvetica, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.3px; line-height: 1.3; text-transform: uppercase;">Stay at CLSU</span>
<span class="hostel-header-name" style="display: block; color: #FFFFFF; font-family: Georgia, 'Times New Roman', Times, serif; font-size: 25px; font-weight: bold; letter-spacing: -0.2px; line-height: 1.18;">{{ $slot }}</span>
</a>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td class="hostel-header-meta" bgcolor="#087443" style="background-color: #087443; border-top: 1px solid #3CCB7F; color: #FFFFFF; padding: 9px 34px 10px; font-family: Arial, Helvetica, sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 1px; line-height: 1.3; text-align: left; text-transform: uppercase;">Reservations &amp; Guest Services <span style="color: #AAF0C4;">&nbsp;&middot;&nbsp;</span> Science City of Muñoz</td>
</tr>
<tr>
<td class="hostel-gold-rule" height="3" bgcolor="#16B364" style="height: 3px; background-color: #16B364; padding: 0; font-size: 0; line-height: 0;">&nbsp;</td>
</tr>
</table>
</td>
</tr>
