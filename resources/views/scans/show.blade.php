@php
    $feedback = $correctionMode['feedback'];
    $total = count($correctionMode['corrections']);
    $unlocated = collect($correctionMode['corrections'])->where('located', false);
@endphp

<x-layouts.app :title="$scan->displayTitle()" active="history">
    <div class="stack-20"
        x-data="correctionMode(@js([
            'corrections' => $correctionMode['corrections'],
            'palette' => $correctionMode['palette'],
            'initial' => $correctionMode['initial'],
            'updateUrl' => route('corrections.update', ['correction' => '__ID__']),
        ]))"
        @keydown.escape.window="selectedId && close()">

        <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-back">
            <x-icon name="chevron-left" :size="18" />Dashboard
        </a>

        <header style="display:flex;flex-direction:column;gap:10px">
            <span class="kicker">Scan #{{ $scan->id }} · Korrektur-Modus</span>
            <h1 class="page-title">{{ $scan->displayTitle() }}</h1>
            <div class="tag-list">
                @foreach ($scan->metaTags() as $tag)
                    <span class="tag tag--navy">{{ $tag }}</span>
                @endforeach
            </div>
        </header>

        {{-- Toolbar: legend/filter per error type + marked/corrected switch --}}
        <div class="toolbar">
            <div class="legend" role="group" aria-label="Fehlertypen ein- oder ausblenden">
                @foreach ($correctionMode['legend'] as $item)
                    <button type="button" class="legend-chip is-{{ $item['type']->slug() }}"
                        @click="toggleType(@js($item['type']->value))"
                        :aria-pressed="(!isHidden(@js($item['type']->value))).toString()" aria-pressed="true">
                        <span class="legend-chip__swatch" aria-hidden="true"></span>
                        {{ $item['type']->label() }}
                        <span class="legend-chip__count">{{ $item['count'] }}</span>
                    </button>
                @endforeach
            </div>
            <div class="seg" role="radiogroup" aria-label="Ansicht">
                <label class="seg__opt">
                    <input type="radio" name="view" value="marked" :checked="!showCorrected" @change="showCorrected = false" checked>Markiert
                </label>
                <label class="seg__opt">
                    <input type="radio" name="view" value="corrected" :checked="showCorrected" @change="showCorrected = true">Korrigiert
                </label>
            </div>
        </div>

        <div class="understood">
            <div class="understood__bar" role="progressbar" aria-label="Verstandene Korrekturen" aria-valuemin="0" aria-valuemax="{{ $total }}" :aria-valuenow="doneCount">
                <span :style="{ width: donePercent }" style="width:0"></span>
            </div>
            <span class="understood__label"><span x-text="doneCount">0</span> / {{ $total }} verstanden</span>
        </div>

        <div class="corr-grid">
            {{-- Left (2/3): the student's text with interactive marks --}}
            <div style="display:flex;flex-direction:column;gap:10px">
                <article class="paper" aria-label="Dein Text">
                    @forelse ($correctionMode['paragraphs'] as $paragraph)
                        @if ($paragraph['heading'])
                            <h3>{{ $paragraph['segments'][0]['text'] }}</h3>
                        @else
                            <x-marked-paragraph :segments="$paragraph['segments']" />
                        @endif
                    @empty
                        <p>Es wurde kein Text erkannt.</p>
                    @endforelse
                </article>
                <span class="paper-hint">
                    <span class="only-desktop">Klicke auf eine Markierung für Erklärung und B2-Äquivalent.</span>
                    <span class="only-mobile">Tippe auf eine Markierung für Erklärung und B2-Äquivalent.</span>
                </span>

                @if ($unlocated->isNotEmpty())
                    <div class="unlocated">
                        <strong>Weitere Hinweise</strong>
                        <span style="color:var(--color-muted)">Diese Stellen konnten im erkannten Text nicht eindeutig markiert werden:</span>
                        <ul style="margin:0;padding-left:18px">
                            @foreach ($unlocated as $item)
                                <li>
                                    <button type="button" class="btn btn-ghost" style="min-height:44px;font-weight:600;text-align:left" @click="select({{ $item['id'] }})">
                                        „{{ $item['wrong'] }}“ → „{{ $item['correct'] }}“
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Right (1/3): detail panel (bottom sheet on phones) + feedback panels --}}
            <aside class="side" aria-label="Erklärungen und Feedback">
                <section class="detail" x-show="selected" x-cloak x-ref="panel" tabindex="-1"
                    :class="selected ? 'is-' + selectedMeta.slug : ''" aria-live="polite" aria-label="Erklärung zur markierten Stelle">
                    <div class="detail__head">
                        <span class="tag tag--type" style="font-weight:800" x-text="selectedMeta.label"></span>
                        <span class="detail__pos">
                            <span class="only-desktop"><span x-text="position"></span> von <span x-text="visible.length"></span></span>
                            <span class="only-mobile"><span x-text="position"></span> / <span x-text="visible.length"></span></span>
                        </span>
                        <button type="button" class="btn btn-icon btn-secondary" @click="step(-1)" aria-label="Vorheriger Fehler">
                            <x-icon name="chevron-left" :size="18" />
                        </button>
                        <button type="button" class="btn btn-icon btn-secondary" @click="step(1)" aria-label="Nächster Fehler">
                            <x-icon name="chevron-right" :size="18" />
                        </button>
                        <button type="button" class="btn btn-icon detail__close" @click="close()" aria-label="Schließen">
                            <x-icon name="x" />
                        </button>
                    </div>
                    <div class="detail__body">
                        <div style="display:flex;flex-direction:column;gap:2px">
                            <span class="mini-label">Dein Text</span>
                            <span class="detail__wrong" x-text="selected?.wrong"></span>
                        </div>
                        <div class="detail__right">
                            <span class="mini-label">B2-Äquivalent</span>
                            <span class="detail__correct" x-text="selected?.correct"></span>
                        </div>
                        <div class="detail__why">
                            <span class="mini-label">Warum?</span>
                            <p x-text="selected?.explanation"></p>
                        </div>
                        <button type="button" class="btn btn-primary" :class="{ 'is-done': selectedUnderstood }" @click="markDone()">
                            <x-icon name="check" :size="18" :stroke="2.5" />
                            <span x-text="selectedUnderstood ? 'Verstanden · weiter' : 'Verstanden'"></span>
                        </button>
                    </div>
                </section>

                @if ($total > 0)
                    <section class="empty-panel only-desktop" x-show="!selected">
                        <span class="empty-panel__count">{{ $total }}</span>
                        <span class="empty-panel__title">Stellen zum Verbessern</span>
                        <p>Klicke auf eine farbige Markierung im Text. Hier erscheint die Erklärung und das passende B2-Äquivalent.</p>
                        <button type="button" class="btn btn-primary" style="padding:12px 16px" @click="selectFirst()">Erste Stelle ansehen</button>
                    </section>
                @endif

                @if ($feedback)
                    <section class="info-panel info-panel--tip">
                        <h2 class="kicker"><x-icon name="lightbulb" :size="18" />Matura-Tipp</h2>
                        <p>{{ $feedback->matura_tip }}</p>
                    </section>
                    <section class="info-panel info-panel--check">
                        <h2 class="kicker kicker--blue">Textsorten-Check · {{ $scan->text_type }}</h2>
                        <p>{{ $feedback->layout_feedback }}</p>
                    </section>
                    <section class="info-panel info-panel--summary">
                        <h2 class="kicker">KI-Zusammenfassung</h2>
                        <p>{{ $feedback->ai_summary }}</p>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.app>
