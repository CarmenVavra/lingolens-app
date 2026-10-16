# Handoff: LingoLens AI – Englisch-Matura Coach (AHS/BHS)

## Overview
LingoLens AI lets Austrian AHS/BHS students photograph handwritten English texts. The app runs OCR (Google Cloud Vision) and AI correction (GPT-4o, B2 Matura level), then shows interactive, colour-coded feedback. Five screens: **Dashboard**, **Setup & Upload**, **Processing**, **Correction mode (split screen)**, **History**. Fully responsive: laptop plus smartphone.

Backend spec (Laravel migrations, `ProcessTextScan` job, `DashboardController`): see `spec/lingolens-blueprint.md` and `spec/lingolens-konzept.pdf`. This README covers the frontend.

## About the Design Files
`LingoLens AI.dc.html` is a **design reference built in HTML**. It is a clickable prototype showing the intended look and behaviour, **not production code**. Recreate it in the target stack. The blueprint suggests **Laravel + Blade + Tailwind CSS**; Livewire or Alpine.js fit the interactive parts (dynamic form, correction panel, polling). Open the HTML file locally in a browser (keep `support.js` and `_ds/` next to it) to click through the flow. Tweak props in the file: `device` (auto/laptop/smartphone), `markStyle` (fill/underline), `simulateFail` (boolean).

All demo data (sample texts, errors, stats, history) is hard-coded in the logic class at the bottom of the HTML file (`SAMPLES`, `BASE_HISTORY`, `STATS`). It maps 1:1 to the DB tables `scans`, `feedbacks` and `corrections`.

## Fidelity
**High-fidelity.** Final colours, typography, spacing and interactions. Recreate it pixel-accurately.

## Design Language
Based on the "Modernist" system: flat, **0 border-radius everywhere** (except the circular lens in the logo and radio dots), strong **2px solid #123C69 rules/borders**, visible grid. Labels are flush left, including inside buttons (`justify-content:flex-start`, trailing icon pushed right with `margin-right:auto` on the label). Font: **Archivo** (400/600/800) for everything. Icons: **Lucide**, 2px stroke, `currentColor`.

## Design Tokens

### Brand palette (from user)
| Token | Hex | Use |
|---|---|---|
| peach | `#EDC7B7` | hero field, highlights on navy, empty-state panel |
| cream | `#EEE2DC` | app background |
| mauve-grey | `#BAB2B5` | processing image well, device-preview desk |
| navy | `#123C69` | primary text, borders, nav bar, score block, selected states |
| raspberry | `#AC3B61` | primary actions, kickers, Matura tip panel, progress |
| raspberry-600 / 700 | `#952F52` / `#7A2443` | hover / pressed of primary; dark kicker text on peach |

### Supporting
| Token | Hex |
|---|---|
| surface (form card, summary) | `#F8F1EE` |
| white (paper, panels) | `#FFFFFF` |
| muted text | `#4A5F7A` |
| body text in student paper | `#20304A` |
| success teal / tint / deep | `#1E9E8B` / `#CDEFE8` / `#0E5E52` |

### Error-type colours (per blueprint)
| error_type | DE label | base | tint (fill) | deep (text) |
|---|---|---|---|---|
| Grammar | Grammatik | `#E0412F` | `#FAD4CC` | `#9E2416` |
| Vocabulary | Vokabular | `#2D7DD2` | `#D3E5F8` | `#164E8C` |
| Style | Stil | `#F2B233` | `#FCEBB8` | `#7A5200` |
| Technical Term | Fachbegriff | `#7E4FC9` | `#E6DAF7` | `#4D2A88` |

### Status colours (scans.status)
- completed → "Fertig": bg `#CDEFE8`, text `#0E5E52`, icon square `#123C69`
- processing → "Wird analysiert": bg `#FCEBB8`, text `#7A5200`, icon `#F2B233`
- failed → "Fehlgeschlagen": bg `#FAD4CC`, text `#9E2416`, icon `#E0412F`

### Typography (Archivo)
- Hero H1: 68px desktop / 44px mobile, 800, line-height .95, letter-spacing -.03em
- Score number: 128px / 96px, 800, lh .85, ls -.04em, colour peach
- Page H1: 44px / 30px, 800, lh 1–1.02
- H2: 30px / 24px, 800
- H3: 20–34px, 800
- Stat number: 60px, 800, lh .9
- Kicker: 12px, 600, uppercase, letter-spacing .12em
- Field mini-labels (Dein Text / B2-Äquivalent / Warum?): 11px, 600, uppercase, ls .1em
- Body: 15–16px, 400, lh 1.5; student text 18px (16px mobile), **lh 2.1**
- Tags: 11–12px, 600, padding 3px 10px

### Spacing / other
4 · 8 · 12 · 16 · 24 · 32px. Page padding 36px 40px (desktop) / 20px 16px (mobile). Card padding 32px / 20px. Max content width 1240px. Shadows: `--shadow-md 0 3px 10px rgba(45,43,43,.16)` (hover on stat cards); sheet shadow `0 -12px 32px rgba(18,60,105,.3)`. Radius 0.

## Responsive Behaviour
Breakpoint **760px**. Below it: mobile layout.
- **Desktop:** 64px navy top nav (logo, links Dashboard / Neuer Scan / Verlauf with a 4px peach bottom bar on the active item, user chip "Lena Gruber · 5CHC · HTL").
- **Mobile:** 56px navy top bar (logo + avatar) and a 68px white **bottom tab bar** (2px navy top border): Start · centre camera button (56×56 raspberry square, raised 22px, 3px white border) · Verlauf. Active tab colour raspberry.
- Grids use `repeat(auto-fit, minmax(min(100%, Npx), 1fr))`, so they collapse to one column on phones.
- Correction mode: desktop `2fr / minmax(320px,1fr)` split with a sticky right panel. Mobile is one column, and the error detail opens as a **bottom sheet** (max-height 72%, 10px top border in the error colour).
- All touch targets ≥ 44px on mobile.

## Screens

### 1. Dashboard
- **Hero row** (2px navy border, two cells, auto-fit ≥360px):
  - Left cell, peach `#EDC7B7`: kicker "Englisch-Matura Coach" (`#7A2443`), H1 "Servus, Lena!", tags [BHS · HTL (navy/cream)] [Chemieingenieurwesen (purple tint/deep)] [5. Jahrgang (cream/navy)], copy "Noch 182 Tage bis zur Matura. Fotografiere deinen handgeschriebenen Text – LingoLens korrigiert ihn auf B2-Niveau.", then the primary CTA **"Neuen Text scannen"**: raspberry, 18px/800, padding 18×22, min-width 320px, camera icon left, arrow right.
  - Right cell, navy: kicker "Matura-Ready-Score" (peach), "68 %", a 20-cell bar (gap 3px, height 22px; filled cells raspberry, next 2 peach, rest `rgba(238,226,220,.18)`), scale labels "0 · B2-sicher ab 80 · 100" (11px mauve), copy "+9 Punkte seit letztem Monat. Größter Hebel: Fachbegriffe."
- **Fehler-Verteilung**: H2 + meta "46 Korrekturen aus 12 Scans". Four stat cards (auto-fit ≥210px, gap 12), **sorted by count desc** (as `DashboardController` returns). Each card: tint bg, 2px navy border, **10px top border in base colour**, label (uppercase, deep), rank "#1", big count (deep), 8px share bar (track `rgba(255,255,255,.7)`, fill base), "37 % aller Fehler". Click → correction mode filtered to that type.
- **Fokus card** (purple `#7E4FC9`, white text): "Dein Fokus diese Woche" / "Fachbegriffe statt Alltagssprache" / explanation / white button "Fachbegriffe üben" → correction filtered to Technical Term, first item selected. Should be derived from the top `error_type`.
- **Letzte Scans**: header with a 2px navy bottom rule + ghost "Alle anzeigen". The 3 latest scans as rows (see History row).

### 2. Setup & Upload ("Weichen-Formular")
Back link (ghost) → kicker "Neuer Scan", H1 "Was hast du geschrieben?", sub-copy. Then one bordered card (`#F8F1EE`) with 3 numbered steps separated by 2px navy rules. Step badges are 32px squares: 1 raspberry, 2 blue `#2D7DD2`, 3 yellow `#F2B233`.
1. **Schultyp**: two radio cards side by side (`.radio` + `.dot`): "AHS – Allgemeinbildende höhere Schule" / "BHS – HTL, HAK, HLW …". Selected card: navy bg + cream text; unselected: white.
   - If **BHS**: "Fachrichtung" `<select>` (48px, 2px navy border, white). Options: Chemieingenieurwesen, Maschinenbau, Elektrotechnik, Informationstechnologie, Wirtschaftsingenieurwesen, Bautechnik, HAK – Wirtschaft, HLW – Wirtschaftliche Berufe.
   - If BHS + **Chemieingenieurwesen**: purple info box "Fachbegriff-Check Chemie aktiv. Alltagssprache wird durch chemische Terminologie ersetzt und lila markiert." (flask icon).
2. **Textsorte**: chip buttons (min-height 48, 2px navy border; selected navy/cream, else white/navy; hover inset 4px raspberry bottom). The list depends on school type:
   - AHS: Essay, Article, Report, Letter to the Editor, Blog Post (default Essay)
   - BHS: Technical Report, Executive Summary, Formal Letter, Email, Essay, Report (default Technical Report)
   - Switching school type resets the text type to the default.
3. **Foto**: dashed 2px navy drop zone (white, hover `#FDF6F2`), peach icon square with camera, label "Foto hochladen" (desktop) / "Foto aufnehmen" (mobile), hint "JPG, PNG oder HEIC · max. 10 MB · gutes Licht, von oben fotografiert". `<input type=file accept="image/*" capture="environment">`. Ghost link "Kein Foto zur Hand? Beispielfoto verwenden". Once a file is chosen: preview row (120×150 thumbnail, "✓ Foto bereit" teal, file name, "Anderes Foto wählen").
- Full-width primary **"Text analysieren →"**, disabled (opacity .4) until an image exists. Submit creates the `scans` row (status `processing`), dispatches `ProcessTextScan`, and navigates to Processing.

### 3. Processing (async queue)
Two cells (2px navy border, max-width 1000): left is the photo (mauve well, 440px / 240px tall) with a **4px raspberry scan line** moving top↔bottom (CSS `top` transition 1.4s linear, glow `0 0 24px 6px rgba(172,59,97,.5)`). Right side: kicker "Scan #13 · Technical Report"; title changes with the step: "LingoLens liest deine Handschrift …" → "Die KI korrigiert deinen Text …" → "Fertig! Dein Feedback ist da."; copy "Dauert meist unter einer Minute. Du kannst die App schließen – wir sagen Bescheid …"; a 12px progress bar (peach track, raspberry fill, width transition .5s); 4 step rows:
1. Foto hochgeladen – Bild sicher übertragen
2. Handschrift erkennen – Google Cloud Vision · OCR
3. KI-Korrektur auf B2-Niveau – GPT-4o · Matura-Kriterien
4. Feedback speichern – Korrekturen & Matura-Tipp

Step square: done = teal "✓", active = yellow number, pending = cream number at 50 % opacity, failed = red "!".
- In production, **poll** `scans.status` (or use broadcasting). On `completed` → go to Correction. On `failed` → error state: red alert square, "Text konnte nicht gelesen werden", tips (light, from above, no shadows), buttons "Neues Foto aufnehmen" (primary) and "Zum Dashboard" (secondary).

### 4. Correction mode (split screen)
- Header: back link, kicker "Scan #12 · Korrektur-Modus", H1 = text title, navy tags [school_type] [department] [text_type].
- **Toolbar** (2px navy rules above and below): legend/filter chips per error type that has errors (2px border in base, tint bg, colour square, label, count). Click toggles visibility: off = transparent bg + line-through, and those marks render as plain text. Right side: segmented control **Markiert | Korrigiert** (Korrigiert swaps every `wrong_text` for `correct_text` in place).
- **Progress**: "X / N verstanden" with a 10px bar (white, 2px navy border, teal fill).
- **Left (2/3)**: white "paper", 2px navy border, padding 36×40 (20×18 mobile), section headings (H4 17px) where present, text 18px lh 2.1. Error spans:
  - default (`fill`): bg tint, 2px bottom border base, weight 600, padding 2px 3px, pointer
  - `underline` variant: no bg, 3px bottom border base
  - selected: bg deep, white text, 3px border base
  - understood: bg `#CDEFE8`, 2px border `#1E9E8B`, weight 400
  - Hint below: "Klicke/Tippe auf eine Markierung für Erklärung und B2-Äquivalent."
- **Right (1/3, sticky)**:
  - **Detail panel** (when an error is selected): white, 2px navy border, **10px top border in error base**. Top row: type tag, "3 von 11", prev/next icon buttons (36px; 44px on mobile). Body: "Dein Text" (18px, line-through red 2px) → "B2-Äquivalent" block (tint bg, 24px/800 deep) → "Warum?" explanation (15px) → primary "✓ Verstanden" (marks it understood and jumps to the next not-yet-understood visible error; if it is already understood, teal "Verstanden · weiter").
  - **Empty state** (desktop, nothing selected): peach panel with the big count "11" (raspberry) "Stellen zum Verbessern", explanation, button "Erste Stelle ansehen".
  - Always visible: **Matura-Tipp** (raspberry field, white text, lightbulb, kicker peach) = `feedbacks.matura_tip`; **Textsorten-Check · {text_type}** (blue tint `#D3E5F8`, 2px navy border) = `feedbacks.layout_feedback`; **KI-Zusammenfassung** (`#F8F1EE`) = `feedbacks.ai_summary`.
- Mobile: the detail panel becomes a bottom sheet with an extra close (X) button; the feedback panels stack below the paper.

### 5. History (Verlauf)
Kicker "Verlauf", H1 "Meine Scans", a list with a 2px navy top rule. Row: grid `auto | 1fr | auto`, padding 16×4, 2px bottom rule `rgba(18,60,105,.18)`, hover `rgba(237,199,183,.45)`. Icon square 48px (status colour) with "#id", title (600, ellipsis), meta "Technical Report · 11 Korrekturen · Heute, 08:14", status tag. Click: completed → Correction; failed → Setup; processing → no action. CTA "Neuen Text scannen" below.

## State Management
- `screen` (dashboard | setup | processing | correction | history). In Laravel these are routes: `/dashboard`, `/scans/create`, `/scans/{id}/processing`, `/scans/{id}`, `/scans`.
- Setup form: `school_type`, `department` (BHS only), `text_type`, `image`.
- Processing: `status` from the server, plus the current step (optionally store step progress on the scan for finer UI).
- Correction (client-side only): `selectedCorrectionId`, `hiddenTypes` (set), `understood` (set; optionally persist per user to feed the score), `showCorrected` (bool).
- Dashboard: `errorStats` (grouped `error_type` counts, desc), Matura-Ready-Score (to be defined by the backend, e.g. based on errors per 100 words over recent scans), latest 3 scans.

### Rendering the marked text
The AI returns `corrections[]` with `original` / `corrected`. To render spans, locate each `wrong_text` inside `raw_extracted_text` (sequentially, first unmatched occurrence) and wrap it. Consider storing `start_offset` / `end_offset` on `corrections` to make this robust.

## Interactions summary
- Hover: stat cards `--shadow-md`; rows peach tint; chips inset raspberry bar; primary `#952F52`, pressed `#7A2443`.
- Focus: `outline: 2px solid #AC3B61; outline-offset: 2px` (never the browser default).
- Transitions: mark bg .15s; progress widths .4–.5s; scan line `top` 1.4s linear.

## Assets
- Logo: 32px raspberry square containing a 14px peach ring (3px stroke). Placeholder; replace with the final logo.
- Icons: Lucide – camera, arrow-right, chevron-left/right, check, x, lightbulb, flask-conical, triangle-alert, house, history.
- No photos. Sample data/text in the HTML is placeholder content.

## Files
- `LingoLens AI.dc.html`: the full clickable prototype (template + logic class with all demo data). Open in a browser.
- `support.js`: runtime needed to open the prototype locally.
- `_ds/…/styles.css`: base design-system tokens and classes (`.btn`, `.tag`, `.input`, `.radio`, `.seg`). The prototype overrides the colour tokens in a `:root` block in its `<helmet>`.
- `spec/lingolens-blueprint.md`, `spec/lingolens-konzept.pdf`: backend and product spec.
