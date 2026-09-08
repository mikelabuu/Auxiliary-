@if ($title)
{{ $title }}
@endif
@foreach ($rows as $label => $value)
- {{ $label }}: {{ $value }}
@endforeach
