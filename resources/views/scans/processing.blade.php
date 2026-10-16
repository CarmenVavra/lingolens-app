@php
    $source = $scan->source;
    $steps = [
        $source->isPhoto() ? ['label' => 'Foto hochgeladen', 'sub' => 'Bild sicher übertragen'] : ['label' => $source->label().' hochgeladen', 'sub' => 'Datei sicher übertragen'],
        $source->readingStep(),
        ['label' => 'KI-Korrektur auf B2-Niveau', 'sub' => 'GPT-4o · Matura-Kriterien'],
        ['label' => 'Feedback speichern', 'sub' => 'Korrekturen & Matura-Tipp'],
    ];
@endphp

<x-layouts.app title="Analyse läuft" active="setup">
    <div class="proc"
        x-data="processingScreen(@js([
            'status' => $scan->status->value,
            'step' => $scan->progress_step,
            'statusUrl' => route('scans.status', $scan),
            'readingTitle' => $source->readingTitle(),
        ]))">
        <div class="proc__image">
            @if ($source->isPhoto() && $scan->file_path)
                <img src="{{ route('scans.preview', $scan) }}" alt="Dein hochgeladenes Foto">
            @elseif (! $source->isPhoto() && ! $scan->uses_sample)
                <div class="proc__doc">
                    <span class="proc__doc-badge {{ $source === App\Enums\ScanSource::Pdf ? 'proc__doc-badge--pdf' : '' }}" aria-hidden="true">
                        <x-icon name="file-text" :size="40" />
                    </span>
                    <span class="tag tag--navy">{{ $source->label() }}</span>
                    @if ($scan->original_name)
                        <span class="proc__doc-name">{{ $scan->original_name }}</span>
                    @endif
                </div>
            @else
                <div class="proc__paper" aria-hidden="true"></div>
            @endif
            <div class="scan-line" x-show="!failed && !done" aria-hidden="true"></div>
        </div>

        <div class="proc__panel">
            <template x-if="!failed">
                <div class="stack-14">
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <span class="kicker">Scan #{{ $scan->id }} · {{ $scan->text_type }}</span>
                        <h1 class="proc__title" x-text="title" aria-live="polite">{{ $source->readingTitle() }}</h1>
                        <p style="font-size:15px">Dauert meist unter einer Minute. Du kannst die App schließen – wir sagen Bescheid, sobald dein Feedback fertig ist.</p>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Fortschritt der Analyse" aria-valuemin="0" aria-valuemax="100"
                        :aria-valuenow="parseInt(percent)">
                        <span :style="{ width: percent }" style="width: {{ $scan->progressPercent() }}%"></span>
                    </div>
                </div>
            </template>

            <template x-if="failed">
                <div class="stack-14" style="align-items:flex-start" role="alert">
                    <span class="alert-square" aria-hidden="true"><x-icon name="triangle-alert" :size="26" /></span>
                    <h1 class="proc__title">Text konnte nicht gelesen werden</h1>
                    <p style="font-size:15px">
                        {{ $scan->failure_reason ?? 'Die Handschrift-Erkennung hat zu wenig Text gefunden.' }}
                        @if ($source->isPhoto())
                            Fotografiere bei gutem Licht, gerade von oben und ohne Schatten.
                        @else
                            Prüfe, ob die Datei nicht passwortgeschützt ist und der Text gut lesbar ist – oder lade sie als PDF hoch.
                        @endif
                    </p>
                </div>
            </template>

            <ol class="proc-steps">
                @foreach ($steps as $index => $step)
                    <li class="proc-step" :class="'is-' + stepState({{ $index }})">
                        <span class="proc-step__mark" aria-hidden="true" x-text="stepMark({{ $index }})">{{ $index + 1 }}</span>
                        <span>
                            <span class="proc-step__label">{{ $step['label'] }}</span>
                            <span class="proc-step__sub">{{ $step['sub'] }}</span>
                            <span class="sr-only-focusable" x-text="{ done: 'erledigt', active: 'läuft', pending: 'ausstehend', failed: 'fehlgeschlagen' }[stepState({{ $index }})]"></span>
                        </span>
                    </li>
                @endforeach
            </ol>

            <template x-if="failed">
                <div style="display:flex;flex-wrap:wrap;gap:10px">
                    <a href="{{ route('scans.create') }}" class="btn btn-primary" style="padding:14px 18px;font-size:15px">
                        {{ $source->isPhoto() ? 'Neues Foto aufnehmen' : 'Andere Datei hochladen' }}
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="padding:14px 18px;font-size:15px">Zum Dashboard</a>
                </div>
            </template>
        </div>
    </div>
</x-layouts.app>
