{{-- Lucide icons (2px stroke, currentColor). Decorative: hidden from screen readers. --}}
@props(['name', 'size' => 20, 'stroke' => 2])

<svg {{ $attributes->merge(['width' => $size, 'height' => $size]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
    stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('camera')
            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z" />
            <circle cx="12" cy="13" r="3" />
        @break

        @case('arrow-right')
            <path d="M5 12h14" />
            <path d="m12 5 7 7-7 7" />
        @break

        @case('chevron-left')
            <path d="m15 18-6-6 6-6" />
        @break

        @case('chevron-right')
            <path d="m9 18 6-6-6-6" />
        @break

        @case('check')
            <path d="M20 6 9 17l-5-5" />
        @break

        @case('x')
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
        @break

        @case('pencil')
            <path d="M21.17 6.81a2.82 2.82 0 0 0-4-4L3.84 16.17a2 2 0 0 0-.5.83l-1.32 4.35a.5.5 0 0 0 .62.62l4.35-1.32a2 2 0 0 0 .83-.5z" />
            <path d="m15 5 4 4" />
        @break

        @case('lightbulb')
            <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" />
            <path d="M9 18h6" />
            <path d="M10 22h4" />
        @break

        @case('flask-conical')
            <path d="M10 2v7.31" />
            <path d="M14 9.3V2" />
            <path d="M8.5 2h7" />
            <path d="M14 9.3a6.5 6.5 0 1 1-4 0" />
        @break

        @case('triangle-alert')
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
        @break

        @case('house')
            <path d="M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" />
        @break

        @case('history')
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
            <path d="M3 3v5h5" />
            <path d="M12 7v5l4 2" />
        @break

        @case('upload')
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <path d="m17 8-5-5-5 5" />
            <path d="M12 3v12" />
        @break

        @case('file-text')
            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
            <path d="M14 2v4a2 2 0 0 0 2 2h4" />
            <path d="M10 9H8" />
            <path d="M16 13H8" />
            <path d="M16 17H8" />
        @break

        @case('log-out')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <path d="m16 17 5-5-5-5" />
            <path d="M21 12H9" />
        @break
    @endswitch
</svg>
