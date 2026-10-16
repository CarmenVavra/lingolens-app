# LingoLens AI online stellen (netcup Webhosting)

Ziel: **https://lingolens.caryssa.at** · PHP 8.3 · SSL-Zertifikat vorhanden

netcup-Webhosting (Plesk) hat kein Node.js und keinen dauerhaft laufenden Queue-Worker. Deshalb:

- Das Upload-Paket (inkl. `vendor/` und gebauten Assets) wird **lokal** gebaut.
- Die Analyse startet direkt nach dem Upload (`LINGOLENS_PROCESS_AFTER_RESPONSE=true`), ein **Cronjob jede Minute** (`schedule:run`) dient als Absicherung und markiert hängengebliebene Scans nach 15 Minuten als fehlgeschlagen.

---

## 1. API-Keys besorgen (ohne Keys läuft nur der Demo-Modus)

Standardmäßig erledigt **OpenAI** beides: Handschrift-Erkennung der Fotos und KI-Korrektur (`LINGOLENS_OCR_PROVIDER=openai`). Dafür reicht der OpenAI-Key.

**Google Cloud Vision** – nur nötig mit `LINGOLENS_OCR_PROVIDER=google` (erste 1.000 Seiten/Monat gratis, danach ca. 1,50 USD pro 1.000). Liest Druckschrift gut, Schreibschrift deutlich schlechter als OpenAI, verarbeitet aber nur in der EU.

1. https://console.cloud.google.com → neues Projekt anlegen, z. B. „LingoLens“.
2. Abrechnung (Billing) für das Projekt aktivieren.
3. „APIs & Dienste“ → „Bibliothek“ → **Cloud Vision API** aktivieren.
4. „APIs & Dienste“ → „Anmeldedaten“ → „Anmeldedaten erstellen“ → **API-Schlüssel**.
5. Schlüssel bearbeiten → **API-Einschränkungen: nur „Cloud Vision API“**.
6. Optional: Budget-Benachrichtigung unter „Abrechnung → Budgets“ anlegen.

**OpenAI** (Handschrift-Erkennung und KI-Korrektur, ca. 0,4 Cent pro Foto plus ca. 2,5 Cent pro Korrektur mit gpt-4o)

1. https://platform.openai.com → Konto, unter „Billing“ Guthaben aufladen.
2. **Budget-Limit** setzen („Limits“), z. B. 10–20 USD/Monat.
3. „API keys“ → neuen Key erstellen (nur einmal sichtbar – sicher aufbewahren).

> ⚠️ OpenAI schaltet `gpt-4o` am **14.01.2027** ab – vor der Matura. Das Modell ist über `OPENAI_MODEL` in der `.env` umstellbar; vor dem Umstieg das neue Modell testen. Neuere Modelle (GPT-5 und später, o-Serie) bekommen automatisch keine `temperature` mitgeschickt, weil sie diese Einstellung ablehnen – es ist also keine Code-Änderung nötig. Sie „denken“ vor der Antwort und brauchen daher etwas länger.

---

## 2. Upload-Paket bauen (lokal, Windows)

```bat
build-release.bat
```

Ergebnis: `build\lingolens-release.zip`. Ins Paket kommt der **letzte Commit** – vorher committen.

Außerdem lokal einen App-Schlüssel erzeugen und notieren:

```bash
php artisan key:generate --show
```

---

## 3. In Plesk einrichten

netcup CCP → **Produkte** → Lupe beim Webhosting → **Plesk** (WCP).

### 3.1 Dateien hochladen

1. **Dateien** → im Hauptverzeichnis des Webspace (neben `httpdocs`) Ordner **`lingolens`** anlegen.
2. `lingolens-release.zip` in diesen Ordner hochladen → Rechtsklick → **Entpacken** → ZIP danach löschen.

Die App liegt damit **außerhalb** des öffentlichen Ordners; nur `lingolens/public` ist im Web erreichbar.

### 3.2 Subdomain auf `public` zeigen lassen

**Websites & Domains** → `lingolens.caryssa.at` → **Hosting-Einstellungen**:

- **Dokumentstamm:** `lingolens/public`
- **SSL/TLS-Unterstützung** an, **„Permanente SEO-sichere 301-Weiterleitung von HTTP zu HTTPS“** an

### 3.3 PHP-Einstellungen

**PHP-Einstellungen** der Subdomain:

| Einstellung | Wert |
|---|---|
| PHP-Version | 8.3, wenn wählbar als **FPM-Anwendung** |
| `memory_limit` | 256M |
| `upload_max_filesize` | 12M |
| `post_max_size` | 16M |
| `max_execution_time` | 300 |

Benötigte Erweiterungen (bei netcup Standard): `pdo_sqlite` (bzw. `pdo_mysql`), `mbstring`, `fileinfo`, `zip`, `dom`, `openssl`, `curl`.

### 3.4 `.env` anlegen

Im Ordner `lingolens`: `.env.production.example` kopieren → umbenennen in **`.env`** → bearbeiten:

- `APP_KEY=` den Schlüssel aus Schritt 2 eintragen (inkl. `base64:`)
- `OPENAI_API_KEY=` eintragen (`GOOGLE_VISION_API_KEY=` nur bei `LINGOLENS_OCR_PROVIDER=google`)
- **`LEGAL_NAME`, `LEGAL_STREET`, `LEGAL_CITY`, `LEGAL_EMAIL`** ausfüllen – erscheinen in Impressum und Datenschutzerklärung (solange sie fehlen, zeigen die Seiten rote Hinweise)
- **E-Mail für Einladungen und „Passwort vergessen“:** `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` und `MAIL_FROM_ADDRESS` eintragen. Benutzername und Absender müssen dasselbe **bestehende Postfach** sein (z. B. `admin@caryssa.at`), sonst lehnt netcup den Versand ab. Port 465 mit `MAIL_SCHEME=smtps`; Port 2525 funktioniert bei netcup nicht. Wer `noreply@caryssa.at` als Absender möchte, legt dafür in Plesk ein eigenes Postfach an.
- **`LINGOLENS_ADMIN_EMAIL=admin@caryssa.at`** – das Admin-Konto. Eine Selbstregistrierung gibt es nicht; nur der Admin legt Schüler:innen an.
- `LINGOLENS_DAILY_SCAN_LIMIT=` (Standard 20 Texte pro Schüler und Tag)

### 3.5 Geplante Aufgaben (Cron)

**Websites & Domains → Geplante Aufgaben → Aufgabe hinzufügen**, Typ **„PHP-Skript ausführen“**, PHP-Version **8.3**:

| Zweck | Skriptpfad | Argumente | Ausführen |
|---|---|---|---|
| Einrichtung (einmalig, nach jedem Update) | `lingolens/artisan` | `lingolens:install` | Zeitplan egal, dann **„Jetzt ausführen“** |
| Warteschlange & Aufräumen | `lingolens/artisan` | `schedule:run` | **Jede Minute** (Cron-Ausdruck `* * * * *`) |

`lingolens:install` legt die SQLite-Datenbank an, führt die Migrationen aus und legt das Admin-Konto aus `LINGOLENS_ADMIN_EMAIL` an. Die Ausgabe zeigt Plesk nach „Jetzt ausführen“ an.

**Admin-Passwort festlegen:** Das Admin-Konto bekommt zunächst ein zufälliges Passwort, das niemand kennt – so steht dein Passwort nirgends in einer Datei. Auf der Login-Seite **„Passwort vergessen?“** → `admin@caryssa.at` eingeben → Link aus der E-Mail öffnen → eigenes Passwort festlegen.

> Mit SSH-Zugang (je nach netcup-Tarif) geht es auch direkt: `php artisan lingolens:install`

### 3.6 Testen

1. https://lingolens.caryssa.at öffnen → Admin-Passwort wie oben festlegen und anmelden.
2. **Schüler:innen** → „Schüler:in anlegen“ (z. B. mit einer zweiten eigenen Adresse) → Einladungs-E-Mail öffnen → Passwort festlegen.
3. „Neuer Scan“ → **Beispieltext verwenden** → Analyse muss in unter einer Minute fertig sein.
4. Ein echtes PDF und ein Foto hochladen.
5. Abmelden → „Passwort vergessen?“ → kommt die E-Mail an? Unter „Mein Konto“ das Passwort ändern.

Fehler stehen in `lingolens/storage/logs/laravel-JJJJ-MM-TT.log`.

---

## 4. Updates einspielen

1. Lokal committen → `build-release.bat`.
2. In Plesk alle Ordner/Dateien in `lingolens` ersetzen, **außer**: `.env`, `database/database.sqlite`, `storage/`.
3. Geplante Aufgabe `lingolens:install` → „Jetzt ausführen“.

---

## 5. Impressum & Datenschutz

- **/impressum** und **/datenschutz** sind eingebaut und auf jeder Seite unten verlinkt (auch auf Login und Einladungsseite).
- Die Betreiberdaten kommen aus der `.env` (`LEGAL_*`), damit sie nicht im GitHub-Repo stehen.
- Beim Annehmen der Einladung muss die Datenschutzerklärung bestätigt werden (inkl. Mindestalter 14); der Zeitpunkt wird gespeichert.
- Löschen auf Anfrage (Art. 17 DSGVO): **Schüler:innen** → „Löschen“ entfernt das Konto mit allen Texten und Korrekturen.
- Keine Drittanbieter beim Seitenaufruf: Die Schrift wird lokal ausgeliefert, kein Tracking, nur technisch notwendige Cookies.
- Fotos und gescannte PDFs liest standardmäßig **OpenAI** (kann auch in den USA verarbeiten, abgesichert durch Standardvertragsklauseln). Die Datenschutzerklärung passt sich an `LINGOLENS_OCR_PROVIDER` automatisch an.
- Mit `LINGOLENS_OCR_PROVIDER=google` läuft Google Vision über den **EU-Endpunkt** (`eu-vision.googleapis.com`) – Fotos werden dann nur in der EU verarbeitet.
- Die Texte sind eine sorgfältige Vorlage, aber **keine Rechtsberatung**. Bitte vor dem Go-live prüfen lassen, insbesondere: Vertragspartner bei Google Cloud und OpenAI (laut deren Auftragsverarbeitungsverträgen), Speicherdauer und ob ein gewerblicher Betrieb vorliegt.
- Mit Google Cloud und OpenAI die jeweiligen **Auftragsverarbeitungsverträge (DPA)** in deren Konsolen akzeptieren.

## 6. Vor dem Go-live sonst noch offen

- **Konto selbst löschen:** derzeit per E-Mail-Anfrage (so in der Datenschutzerklärung beschrieben).
- **Keine Demo-Daten** auf dem Server (`db:seed` nicht ausführen).
