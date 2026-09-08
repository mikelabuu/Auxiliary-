<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.header-shell {
width: 100% !important;
}

.footer {
width: 100% !important;
}

.body {
padding: 0 !important;
}
}

@media only screen and (max-width: 500px) {
.header {
padding: 10px 0 0 !important;
}

.hostel-header-banner {
padding: 21px 20px 20px !important;
}

.hostel-logo-cell {
width: 58px !important;
padding-right: 12px !important;
}

.hostel-logo {
width: 46px !important;
height: 46px !important;
}

.hostel-header-name {
font-size: 21px !important;
}

.hostel-header-meta {
display: block !important;
padding: 10px 20px !important;
text-align: left !important;
}

.content-cell {
padding: 31px 22px 34px !important;
}

.alert-card-cell {
padding: 18px !important;
}

.alert-detail-label,
.alert-detail-value,
.mail-details-label,
.mail-details-value {
display: block !important;
width: auto !important;
}

.alert-detail-label,
.mail-details-label {
padding: 10px 0 2px !important;
}

.alert-detail-value,
.mail-details-value {
border-top: 0 !important;
padding: 0 0 10px !important;
}

.button {
width: 100% !important;
text-align: center !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body>

@isset($preheader)
<div class="preheader" style="display: none; max-height: 0; max-width: 0; overflow: hidden; opacity: 0; color: transparent; font-size: 1px; line-height: 1px; mso-hide: all;">
{!! $preheader !!}
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
</div>
@endisset

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#F4F8F5">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;" bgcolor="#F4F8F5">
<table class="inner-body" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#FFFFFF">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
