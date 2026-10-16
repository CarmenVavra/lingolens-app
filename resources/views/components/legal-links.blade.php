{{-- Footer links to the legal pages (required on every page). --}}
<nav {{ $attributes->merge(['class' => 'legal-links']) }} aria-label="Rechtliches">
    <a href="{{ route('legal.imprint') }}" @if (request()->routeIs('legal.imprint')) aria-current="page" @endif>Impressum</a>
    <a href="{{ route('legal.privacy') }}" @if (request()->routeIs('legal.privacy')) aria-current="page" @endif>Datenschutz</a>
</nav>
