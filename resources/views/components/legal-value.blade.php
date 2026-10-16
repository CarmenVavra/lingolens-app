{{-- Operator detail from .env; shows a visible hint while it is not configured yet. --}}
@props(['value', 'env'])

@if (filled($value)){{ $value }}@else<mark class="legal-missing">[{{ $env }} in .env eintragen]</mark>@endif
