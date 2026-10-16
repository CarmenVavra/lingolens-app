<x-layouts.app title="Verlauf" active="history">
    <div class="stack-20 narrow">
        <div class="page-intro">
            <span class="kicker">Verlauf</span>
            <h1 class="page-title">Meine Scans</h1>
        </div>

        @if ($scans->isEmpty())
            <div class="empty-stats">
                <strong>Noch keine Scans.</strong>
                <span>Fotografiere deinen ersten handgeschriebenen Text.</span>
            </div>
        @else
            <ul class="scan-list scan-list--ruled scan-list--large" role="list">
                @foreach ($scans as $scan)
                    <x-scan-row :scan="$scan" />
                @endforeach
            </ul>

            @if ($scans->hasPages())
                <nav class="pager" aria-label="Seiten">
                    @if ($scans->previousPageUrl())
                        <a href="{{ $scans->previousPageUrl() }}" class="btn btn-secondary"><x-icon name="chevron-left" :size="18" />Neuere</a>
                    @else
                        <span></span>
                    @endif
                    @if ($scans->nextPageUrl())
                        <a href="{{ $scans->nextPageUrl() }}" class="btn btn-secondary">Ältere<x-icon name="chevron-right" :size="18" /></a>
                    @endif
                </nav>
            @endif
        @endif

        <a href="{{ route('scans.create') }}" class="btn btn-primary" style="font-size:16px;padding:16px 20px;justify-content:flex-start;gap:10px;align-self:flex-start">
            <x-icon name="camera" />Neuen Text scannen
        </a>
    </div>
</x-layouts.app>
