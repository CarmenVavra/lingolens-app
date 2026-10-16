<x-layouts.app title="Neuer Scan" active="setup">
    <div class="stack-24 narrow"
        x-data="setupForm(@js([
            'school' => $schoolType->value,
            'department' => $department,
            'textType' => $textType,
            'textTypes' => $textTypes,
            'chemistry' => config('lingolens.chemistry_department'),
            'extensions' => $extensions,
            'photoFormats' => $photoFormats,
            'maxBytes' => config('lingolens.max_upload_kb') * 1024,
        ]))">
        <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-back">
            <x-icon name="chevron-left" :size="18" />Dashboard
        </a>

        <div class="page-intro">
            <span class="kicker">Neuer Scan</span>
            <h1 class="page-title">Was hast du geschrieben?</h1>
            <p>LingoLens passt die Korrektur an Schultyp, Fachrichtung und Textsorte an.</p>
            @if ($remainingScans !== null)
                <p class="setup-step__hint">Heute noch {{ $remainingScans }} {{ $remainingScans === 1 ? 'Text' : 'Texte' }} möglich.</p>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert-error" role="alert">
                <strong>Bitte überprüfe deine Angaben:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('scans.store') }}" enctype="multipart/form-data" class="stack-24" @submit="submit($event)">
            @csrf
            <input type="hidden" name="use_sample" :value="useSample ? 1 : 0" value="0">

            <div class="setup-card">
                {{-- 1. School type --}}
                <fieldset class="setup-step">
                    <legend class="setup-step__head" style="float:left;width:100%">
                        <span class="step-badge step-badge--1" aria-hidden="true">1</span>
                        <h2>Schultyp</h2>
                    </legend>
                    <div class="radio-grid">
                        @foreach (App\Enums\SchoolType::cases() as $type)
                            <label class="radio-card">
                                <input type="radio" name="school_type" value="{{ $type->value }}"
                                    @checked($schoolType === $type)
                                    @change="pickSchool('{{ $type->value }}')">
                                <span class="dot" aria-hidden="true"></span>
                                <span>
                                    <span class="radio-card__title">{{ $type->value }}</span>
                                    <span class="radio-card__sub">{{ $type->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="field" x-show="isBhs" @if ($schoolType !== App\Enums\SchoolType::BHS) x-cloak @endif>
                        <label for="department">Fachrichtung</label>
                        <select id="department" name="department" class="select" x-model="department" :disabled="!isBhs"
                            @error('department') aria-invalid="true" aria-describedby="department-error" @enderror>
                            @foreach ($departments as $option)
                                <option value="{{ $option }}" @selected($department === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('department')
                            <span id="department-error" class="field-error">{{ $message }}</span>
                        @enderror
                        <div class="info-box info-box--chem" x-show="isChemistry" role="note">
                            <x-icon name="flask-conical" :size="18" />
                            <span><b>Fachbegriff-Check Chemie aktiv.</b> Alltagssprache wird durch chemische Terminologie ersetzt und lila markiert.</span>
                        </div>
                    </div>
                </fieldset>

                {{-- 2. Text type (list depends on the school type) --}}
                <fieldset class="setup-step">
                    <legend class="setup-step__head" style="float:left;width:100%">
                        <span class="step-badge step-badge--2" aria-hidden="true">2</span>
                        <h2>Textsorte</h2>
                        <span class="setup-step__hint">Matura-Textsorten <span x-text="school">{{ $schoolType->value }}</span></span>
                    </legend>
                    <div class="chip-list">
                        <template x-for="type in types" :key="school + type">
                            <label class="chip">
                                <input type="radio" name="text_type" :value="type" x-model="textType">
                                <span x-text="type"></span>
                            </label>
                        </template>
                    </div>
                    @error('text_type')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </fieldset>

                {{-- 3. Photo --}}
                {{-- 3. The text: photo, PDF, Word or TXT – handwritten or typed --}}
                <fieldset class="setup-step">
                    <legend class="setup-step__head" style="float:left;width:100%">
                        <span class="step-badge step-badge--3" aria-hidden="true">3</span>
                        <h2>Dein Text</h2>
                        <span class="setup-step__hint">handschriftlich oder am Laptop geschrieben</span>
                    </legend>

                    @if ($demoMode)
                        <p class="info-box info-box--demo" role="note">
                            <x-icon name="lightbulb" :size="18" />
                            <span><b>Demo-Modus:</b> Ohne API-Keys analysiert LingoLens einen Beispieltext statt deiner Datei.</span>
                        </p>
                    @endif

                    {{-- The only submitted field; camera and drag & drop hand their file over to it. --}}
                    <input type="file" id="file" name="file" x-ref="file" class="sr-only-focusable"
                        accept="{{ $accept }}" aria-describedby="file-hint" @change="onFile($event)">
                    <input type="file" id="camera" x-ref="camera" class="sr-only-focusable" tabindex="-1"
                        accept="image/*" capture="environment" @change="onCamera($event)">

                    <div x-show="!hasFile" class="stack-14" style="align-items:stretch">
                        <div class="upload-options">
                            <label for="camera" class="dropzone dropzone--camera only-mobile">
                                <span class="dropzone__icon" aria-hidden="true"><x-icon name="camera" :size="28" /></span>
                                <span class="dropzone__title">Foto aufnehmen</span>
                                <span class="dropzone__hint">Handschrift direkt fotografieren · gutes Licht, von oben</span>
                            </label>

                            <label for="file" class="dropzone" :class="{ 'is-dragging': dragging }"
                                @dragenter.prevent="dragging = true" @dragover.prevent="dragging = true"
                                @dragleave.prevent="dragging = false" @drop.prevent="onDrop($event)">
                                <span class="dropzone__icon" aria-hidden="true"><x-icon name="upload" :size="28" /></span>
                                <span class="dropzone__title">
                                    <span class="only-desktop">Datei hochladen oder hierher ziehen</span>
                                    <span class="only-mobile">Datei hochladen</span>
                                </span>
                                <span class="format-list" aria-hidden="true">
                                    <span class="format-tag format-tag--pdf">PDF</span>
                                    <span class="format-tag">Foto</span>
                                    <span class="format-tag">Word</span>
                                    <span class="format-tag">TXT</span>
                                </span>
                                <span id="file-hint" class="dropzone__hint">
                                    PDF, {{ $photoFormats }}, Word (.docx) oder TXT · max. 10 MB.
                                    Getippte PDFs werden direkt ausgelesen, Scans und Fotos per Handschrift-Erkennung.
                                </span>
                            </label>
                        </div>
                        <button type="button" class="btn btn-ghost" style="align-self:flex-start" @click="useDemo()">Keinen Text zur Hand? Beispieltext verwenden</button>
                    </div>

                    <div x-show="hasFile" x-cloak class="preview-row">
                        <div class="preview-row__thumb">
                            <template x-if="previewUrl && !previewBroken">
                                <img :src="previewUrl" alt="Vorschau deines Fotos" x-on:error="previewBroken = true">
                            </template>
                            <template x-if="useSample || (kind === 'photo' && previewBroken)">
                                <div class="paper-lines" x-text="useSample ? 'Beispieltext' : 'Foto'"></div>
                            </template>
                            <template x-if="!useSample && kind !== 'photo'">
                                <div class="doc-thumb" :class="'doc-thumb--' + kind" aria-hidden="true">
                                    <x-icon name="file-text" :size="36" />
                                    <span x-text="kindLabel"></span>
                                </div>
                            </template>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:6px;min-width:0">
                            <span class="preview-row__ready" role="status">
                                <x-icon name="check" :size="16" :stroke="2.5" /><span x-text="readyLabel">Datei bereit</span>
                            </span>
                            <span class="preview-row__name" x-text="fileName"></span>
                            <span class="preview-row__meta" x-show="fileMeta" x-text="fileMeta"></span>
                            <button type="button" class="btn btn-ghost" style="align-self:flex-start" @click="clear(); $nextTick(() => $refs.file.click())">
                                Andere Datei wählen
                            </button>
                        </div>
                    </div>

                    <span class="field-error" role="alert" x-show="error" x-text="error" x-cloak></span>
                    @error('file')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </fieldset>
            </div>

            <button type="submit" class="btn btn-primary btn-block" :disabled="!hasFile || submitting" disabled>
                <span class="btn-label" x-text="submitting ? 'Wird hochgeladen …' : 'Text analysieren'">Text analysieren</span>
                <x-icon name="arrow-right" />
            </button>
        </form>
    </div>
</x-layouts.app>
