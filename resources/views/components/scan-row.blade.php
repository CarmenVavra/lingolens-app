{{--
    One scan in the dashboard / history list.
    Completed → correction mode, failed → new scan, processing → no action.
--}}
@props(['scan'])

@php
    $href = match (true) {
        $scan->isCompleted() => route('scans.show', $scan),
        $scan->isFailed() => route('scans.create'),
        default => null,
    };
    $meta = collect([
        $scan->text_type,
        $scan->source->isPhoto() ? null : $scan->source->label(),
        $scan->isCompleted() ? $scan->corrections_count.' Korrekturen' : null,
        $scan->displayDate(),
    ])->filter()->implode(' · ');
    $tag = $href ? 'a' : 'div';
@endphp

<li>
    <{{ $tag }} @if ($href) href="{{ $href }}" @endif class="scan-row"
        @if ($scan->isFailed()) aria-label="Scan #{{ $scan->id }} {{ $scan->displayTitle() }} – fehlgeschlagen, neuen Scan starten" @endif>
        <span class="scan-row__icon scan-row__icon--{{ $scan->status->slug() }}" aria-hidden="true">#{{ $scan->id }}</span>
        <span class="scan-row__body">
            <span class="scan-row__title">{{ $scan->displayTitle() }}</span>
            <span class="scan-row__meta">{{ $meta }}</span>
        </span>
        <span class="tag status-tag--{{ $scan->status->slug() }}">{{ $scan->status->label() }}</span>
    </{{ $tag }}>
</li>
