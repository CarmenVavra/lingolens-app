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

                @if ($feedback?->task_fit)
                    <section class="info-panel info-panel--task is-{{ $feedback->task_fit->slug() }}" aria-labelledby="task-check-title">
                        <h2 id="task-check-title" class="kicker">Aufgaben-Check</h2>
                        <span class="task-verdict">
                            <x-icon :name="$feedback->task_fit->icon()" :size="18" :stroke="2.5" />{{ $feedback->task_fit->label() }}
                        </span>
                        @if ($feedback->task_feedback)
                            <p>{{ $feedback->task_feedback }}</p>
                        @endif
                        @if ($feedback->taskTips())
                            <span class="mini-label">So erfüllst du die Aufgabe besser</span>
                            <ul class="task-tips">
                                @foreach ($feedback->taskTips() as $tip)
                                    <li>
                                        {{ $tip['tip'] }}
                                        @if ($tip['example'] !== '')
                                            <span class="task-tip__example"><span class="sr-only">Beispiel: </span><span lang="en">{{ $tip['example'] }}</span></span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($scan->task_text)
                            <details class="task-prompt">
                                <summary>Aufgabenstellung anzeigen</summary>
                                <p>{{ $scan->task_text }}</p>
                            </details>
                        @endif
                    </section>
                @elseif ($feedback && $scan->hasTask())
                    <section class="info-panel info-panel--summary" role="note">
                        <h2 class="kicker">Aufgaben-Check</h2>
                        <p>Die Aufgabenstellung konnte nicht gelesen werden. Fotografiere sie beim nächsten Mal gerade und gut beleuchtet oder tippe sie ein.</p>
                    </section>
                @endif

                @if ($feedback?->rubric)
                    @php($rubric = $feedback->rubric)
                    <section class="info-panel info-panel--rubric" aria-labelledby="rubric-title">
                        <h2 id="rubric-title" class="kicker">{{ config('srdp.name') }}</h2>
                        @if ($rubric['total'] !== null)
                            <p class="rubric-total">
                                <span class="rubric-total__points">{{ $rubric['total'] }}</span>
                                <span>von {{ $rubric['max'] }} Punkten</span>
                            </p>
                        @endif
                        @if (($rubric['grade'] ?? null) !== null)
                            <div class="rubric-grade is-grade-{{ $rubric['grade'] }}">
                                <span class="rubric-grade__number" aria-hidden="true">{{ $rubric['grade'] }}</span>
                                <span>
                                    <span class="rubric-grade__label">Notenschätzung: {{ App\Services\Rubric\SrdpRubric::gradeLabel($rubric['grade']) }} ({{ $rubric['grade'] }})</span>
                                    <span class="rubric-grade__hint">
                                        @if ($rubric['off_topic'])
                                            Themenverfehlung – die Arbeit wäre negativ.
                                        @else
                                            {{ $rubric['percent'] }} % der Punkte auf Niveau {{ $rubric['level'] ?? 'B2' }}
                                        @endif
                                    </span>
                                </span>
                            </div>
                        @elseif ($rubric['total'] !== null)
                            <p class="rubric-meta">Eine Notenschätzung gibt es, wenn du die Aufgabenstellung mitschickst.</p>
                        @endif
                        <dl class="rubric-list">
                            @foreach (config('srdp.criteria') as $key => $criterion)
                                @php($item = $rubric['criteria'][$key] ?? ['score' => null, 'reason' => ''])
                                <div class="rubric-item">
                                    <dt>
                                        <span>{{ $criterion['label'] }}</span>
                                        <span class="rubric-item__score">{{ $item['score'] ?? '–' }}<span class="rubric-item__max"> / {{ config('srdp.max_per_criterion') }}</span></span>
                                    </dt>
                                    @if ($item['score'] !== null)
                                        <dd class="rubric-bar" role="img" aria-label="{{ $item['score'] }} von {{ config('srdp.max_per_criterion') }} Punkten">
                                            <span style="width: {{ $item['score'] * 10 }}%"></span>
                                        </dd>
                                    @endif
                                    @if ($item['reason'] !== '')
                                        <dd class="rubric-item__reason">{{ $item['reason'] }}</dd>
                                    @endif
                                </div>
                            @endforeach
                        </dl>
                        @if ($rubric['word_penalty'])
                            <p class="rubric-note" role="note">
                                Dein Text hat etwa {{ $rubric['word_count'] }} Wörter, gefordert sind etwa {{ $rubric['required_words'] }}.
                                Mehr als 10 % Abweichung: „Erfüllung der Aufgabenstellung“ wurde um eine Stufe herabgesetzt.
                            </p>
                        @elseif ($rubric['required_words'])
                            <p class="rubric-meta">Wortanzahl: etwa {{ $rubric['word_count'] }} von geforderten {{ $rubric['required_words'] }} – passt.</p>
                        @endif
                        <p class="rubric-meta">KI-Einschätzung als Orientierung – die Note vergibt deine Lehrkraft.</p>
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
