<x-layouts.app title="Dashboard" active="dashboard">
    <div class="stack-32">
        {{-- Hero: greeting + Matura-Ready-Score --}}
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero__intro">
                <span class="kicker kicker--dark">Englisch-Matura Coach</span>
                <h1 id="hero-title" class="hero__title">Servus, {{ $user->firstName() }}!</h1>
                <div class="tag-list">
                    <span class="tag tag--navy">{{ $user->schoolLabel() }}</span>
                    @if ($user->department)
                        <span class="tag tag--type is-technical">{{ $user->department }}</span>
                    @endif
                    @if ($user->gradeLabel())
                        <span class="tag tag--cream">{{ $user->gradeLabel() }}</span>
                    @endif
                </div>
                <p class="hero__copy">
                    Noch {{ $dashboard['daysUntilMatura'] }} Tage bis zur Matura. Fotografiere deinen handgeschriebenen Text oder lade ein PDF hoch – LingoLens korrigiert ihn auf B2-Niveau.
                </p>
                <a href="{{ route('scans.create') }}" class="btn btn-primary btn-hero">
                    <x-icon name="camera" :size="22" />
                    <span class="btn-label">Neuen Text scannen</span>
                    <x-icon name="arrow-right" />
                </a>
            </div>

            <div class="hero__score">
                <h2 class="kicker kicker--peach" id="score-title">Matura-Ready-Score</h2>
                <div class="score-value" aria-describedby="score-title">
                    <span class="score-value__number">{{ $dashboard['score'] ?? '–' }}</span>
                    <span class="score-value__unit">%</span>
                </div>
                <div class="score-bar" role="meter" aria-labelledby="score-title"
                    aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $dashboard['score'] ?? 0 }}">
                    @foreach ($dashboard['scoreCells'] as $cell)
                        <span class="is-{{ $cell }}"></span>
                    @endforeach
                </div>
                <div class="score-scale" aria-hidden="true"><span>0</span><span>B2-sicher ab 80</span><span>100</span></div>
                <p class="score-copy">
                    @if ($dashboard['score'] === null)
                        Noch keine Daten. Scanne deinen ersten Text, um deinen Score zu sehen.
                    @else
                        @if ($dashboard['scoreDelta'] !== null)
                            {{ $dashboard['scoreDelta'] >= 0 ? '+' : '−' }}{{ abs($dashboard['scoreDelta']) }} Punkte seit letztem Monat.
                        @endif
                        @if ($dashboard['lever'])
                            Größter Hebel: {{ $dashboard['lever']->pluralLabel() }}.
                        @endif
                    @endif
                </p>
            </div>
        </section>

        {{-- Error distribution, sorted by count --}}
        <section class="stack-14" aria-labelledby="stats-title">
            <div class="section-head">
                <h2 id="stats-title" class="section-title">Deine Fehler-Verteilung</h2>
                <span class="section-head__meta">{{ $dashboard['totalCorrections'] }} Korrekturen aus {{ $dashboard['completedScans'] }} Scans</span>
            </div>

            @if ($dashboard['stats'] === [])
                <div class="empty-stats">
                    <strong>Noch keine Korrekturen.</strong>
                    <span>Sobald dein erster Text analysiert ist, siehst du hier, woran du arbeiten solltest.</span>
                </div>
            @else
                <ul class="stat-grid" role="list" style="margin:0;padding:0;list-style:none">
                    @foreach ($dashboard['stats'] as $stat)
                        <li style="display:flex">
                            <a href="{{ $stat['url'] ?? route('scans.index') }}" class="stat-card is-{{ $stat['type']->slug() }}" style="flex:1"
                                aria-label="{{ $stat['type']->label() }}: {{ $stat['count'] }} Fehler, {{ $stat['percent'] }} % aller Fehler, Rang {{ $stat['rank'] }} – Stellen ansehen">
                                <span class="stat-card__head">
                                    <span class="stat-card__label">{{ $stat['type']->label() }}</span>
                                    <span class="stat-card__rank">#{{ $stat['rank'] }}</span>
                                </span>
                                <span class="stat-card__count">{{ $stat['count'] }}</span>
                                <span class="stat-card__bar"><span style="width: {{ $stat['percent'] }}%"></span></span>
                                <span class="stat-card__share">{{ $stat['percent'] }} % aller Fehler</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="two-col">
            {{-- Weekly focus, derived from the top error type --}}
            @if ($dashboard['focus'])
                @php($focus = $dashboard['focus'])
                <section class="focus-card is-{{ $focus['type']->slug() }}" aria-labelledby="focus-title">
                    <span class="kicker focus-card__kicker">Dein Fokus diese Woche</span>
                    <h3 id="focus-title" class="focus-card__title">{{ $focus['title'] }}</h3>
                    <p class="focus-card__text">{{ $focus['text'] }}</p>
                    @if ($focus['url'])
                        <a href="{{ $focus['url'] }}" class="btn btn-white">
                            {{ $focus['button'] }}
                            <x-icon name="arrow-right" :size="18" />
                        </a>
                    @endif
                </section>
            @else
                <section class="focus-card is-technical" aria-labelledby="focus-title">
                    <span class="kicker focus-card__kicker">Dein Fokus diese Woche</span>
                    <h3 id="focus-title" class="focus-card__title">Erster Scan</h3>
                    <p class="focus-card__text">Fotografiere einen Text, den du für die Matura geschrieben hast. Danach zeigen wir dir, wo dein größter Hebel liegt.</p>
                    <a href="{{ route('scans.create') }}" class="btn btn-white">Jetzt scannen <x-icon name="arrow-right" :size="18" /></a>
                </section>
            @endif

            {{-- Latest scans --}}
            <section aria-labelledby="recent-title" style="display:flex;flex-direction:column">
                <div class="list-head">
                    <h3 id="recent-title">Letzte Scans</h3>
                    <a href="{{ route('scans.index') }}" class="btn btn-ghost">Alle anzeigen</a>
                </div>
                @if ($dashboard['recentScans']->isEmpty())
                    <p style="padding:14px 4px;color:var(--color-muted)">Noch keine Scans vorhanden.</p>
                @else
                    <ul class="scan-list" role="list">
                        @foreach ($dashboard['recentScans'] as $scan)
                            <x-scan-row :scan="$scan" />
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
