# Profi-Tipp für Laravel-Entwickler: "Laravel Boost MCP"

Um Claude Code noch mächtiger im Umgang mit Ihrer Datenbank zu machen, empfiehlt sich die Installation des Open-Source-Pakets `laravel/boost` via Composer im Projekt:

```bash
composer require laravel/boost --dev
```

## Dieses Paket registriert sich als sogenannter Model Context Protocol (MCP) Server. Dadurch kann Claude Code direkt mit den Internals Ihrer Laravel-App interagieren. Claude kann dann

- Live Ihre echte lokale Datenbank-Struktur abfragen (ohne die Dateien lesen zu müssen).
- Artisan Tinker-Befehle im Hintergrund ausführen, um Daten direkt zu prüfen.

## Server start and stop script

- Mach im Projektordner eine start.bat, um den Server zu starten
- Mach im Projektordner eine stop.bat, um den Server zu soppen

---

# LingoLens AI – Englisch-Matura Coach (AHS/BHS)

Laravel 12 · Blade · Tailwind CSS 4 · Alpine.js · SQLite · Queue (database)

Schüler:innen fotografieren handgeschriebene englische Texte. Eine KI (OpenAI GPT-4o, alternativ Google Cloud Vision) liest die Handschrift, GPT-4o korrigiert auf B2-Matura-Niveau, die App zeigt interaktives, farbcodiertes Feedback. Umsetzung nach `UI_Mockups/design_handoff_lingolens_ai`, `lingolens-blueprint.md` und `lingolens-konzept.pdf`. Responsiv für Laptop und Smartphone (Breakpoint 760 px).

## Texte einreichen

| Format | Verarbeitung |
|---|---|
| Foto (JPG, PNG; HEIC nur mit Google) | Handschrift-Erkennung (OpenAI, per `LINGOLENS_OCR_PROVIDER=google` Google Cloud Vision) |
| PDF, am Laptop geschrieben | Textebene wird direkt ausgelesen – keine OCR nötig |
| PDF, gescannt / handschriftlich | Handschrift-Erkennung der PDF-Seiten (OpenAI bzw. Google Vision, erste 5 Seiten) |
| Word (.docx), TXT | Text wird direkt übernommen |

Max. 10 MB pro Datei, max. ca. 2.000 Wörter. Ob ein PDF getippt oder gescannt ist, erkennt LingoLens automatisch. Hochgeladene Dateien werden nach der Analyse gelöscht.

## Starten / Stoppen

- `start.bat` – installiert bei Bedarf Abhängigkeiten, migriert die Datenbank (beim ersten Start mit Demo-Daten), startet Queue-Worker und Server auf **http://localhost:8250**
- `stop.bat` – beendet Server und Queue-Worker

Demo-Login: siehe `database/seeders/DatabaseSeeder.php`.

## API-Keys / Demo-Modus

In `.env` eintragen:

```env
OPENAI_API_KEY=...
# optional: Google Cloud Vision statt OpenAI für die Handschrift
# LINGOLENS_OCR_PROVIDER=google
# GOOGLE_VISION_API_KEY=...
```

Ohne Key läuft die App im **Demo-Modus**: OCR und KI-Korrektur werden durch die Beispieltexte aus dem Design-Handoff ersetzt (`LINGOLENS_DEMO_MODE=true|false` erzwingt einen Modus).

## Screens & Routen

| Screen | Route |
|---|---|
| Dashboard | `/dashboard` |
| Setup & Upload | `/scans/create` |
| Processing (Polling) | `/scans/{id}/processing` |
| Korrektur-Modus (Split-Screen) | `/scans/{id}` |
| Verlauf | `/scans` |

## Entwicklung

```bash
php artisan test              # Feature- und Unit-Tests
./vendor/bin/phpstan analyse  # Larastan, Level 6
./vendor/bin/pint             # Code-Style
npm run build                 # Vite-Build
```
