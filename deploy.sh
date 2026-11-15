#!/usr/bin/env bash
# ============================================================================
#  LingoLens AI - Deployment auf netcup (Plesk, Subdomain lingolens.caryssa.at)
#
#  Ablauf: Git-Stand pruefen -> Tests, Pint, PHPStan -> Release lokal bauen
#  (git archive + composer --no-dev + npm run build) -> per scp hochladen ->
#  per ssh aktivieren (Wartungsmodus, Datenbank-Sicherung, Code tauschen,
#  Migrationen, Caches). Auf dem Server werden weder git noch composer noch
#  node benoetigt.
#
#  Aufruf:
#    ./deploy.sh                Release bauen, hochladen und aktivieren
#    ./deploy.sh --skip-tests   nur im Notfall: ohne Tests, Pint und PHPStan
#    ./deploy.sh --build-only   Release lokal bauen, nichts hochladen (zum Pruefen)
#    ./deploy.sh --allow-dirty  trotz nicht committeter Aenderungen (deployt wird HEAD)
#    ./deploy.sh --rollback     vorherigen Code wiederherstellen (Datenbank bleibt)
#
#  Konfiguration: .deploy.env neben diesem Skript (Vorlage: .deploy.env.example).
#
#  Layout auf dem Server (relativ zum SSH-Home = Webspace-Root):
#    lingolens.caryssa.at/lingolens/          die laufende App, Dokumentstamm .../lingolens/public
#      .env, database/database.sqlite, storage/   bleiben bei jedem Deployment unangetastet
#    lingolens.caryssa.at/deploy/previous/<release>/   ersetzter Code (fuer --rollback)
#    lingolens.caryssa.at/deploy/backups/              Datenbank-Sicherungen vor jeder Migration
#    lingolens.caryssa.at/deploy/uploads/              temporaer
#
#  Der Code wird im bestehenden Ordner getauscht (kein Symlink): Dokumentstamm,
#  geplante Aufgaben in Plesk (lingolens/artisan) und Pfade bleiben gleich.
# ============================================================================
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

MODE=deploy
SKIP_TESTS=0
ALLOW_DIRTY=0
for arg in "$@"; do
    case "$arg" in
        --build-only) MODE=build ;;
        --rollback) MODE=rollback ;;
        --skip-tests) SKIP_TESTS=1 ;;
        --allow-dirty) ALLOW_DIRTY=1 ;;
        -h | --help) sed -n '2,29p' "${BASH_SOURCE[0]}"; exit 0 ;;
        *) echo "Unbekannte Option: $arg (siehe ./deploy.sh --help)" >&2; exit 2 ;;
    esac
done

if [[ -f .deploy.env ]]; then
    # shellcheck disable=SC1091
    source .deploy.env
fi

DEPLOY_PORT="${DEPLOY_PORT:-22}"
DEPLOY_SITE="${DEPLOY_SITE:-lingolens.caryssa.at}"
DEPLOY_APP_DIR="${DEPLOY_APP_DIR:-lingolens}"
DEPLOY_PHP="${DEPLOY_PHP:-}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
DEPLOY_KEEP="${DEPLOY_KEEP:-3}"
DEPLOY_URL="${DEPLOY_URL:-https://lingolens.caryssa.at}"

if [[ $MODE != build ]]; then
    : "${DEPLOY_USER:?DEPLOY_USER fehlt - .deploy.env aus .deploy.env.example anlegen}"
    : "${DEPLOY_HOST:?DEPLOY_HOST fehlt - .deploy.env aus .deploy.env.example anlegen}"
fi

REMOTE="${DEPLOY_USER:-}@${DEPLOY_HOST:-}"
APP="$DEPLOY_SITE/$DEPLOY_APP_DIR"
WORKDIR="$DEPLOY_SITE/deploy"

step() { printf '\n\033[1;33m==> %s\033[0m\n' "$*"; }
fail() { printf '\n\033[1;31m[FEHLER] %s\033[0m\n' "$*" >&2; exit 1; }

ssh_run() { ssh -p "$DEPLOY_PORT" "$REMOTE" "$@"; }
# Zieldateinamen immer explizit angeben (MSYS-scp unter Git Bash uebernimmt sonst den ganzen Pfad).
scp_put() { scp -q -P "$DEPLOY_PORT" "$1" "$REMOTE:$2"; }

for tool in ssh scp tar; do
    command -v "$tool" >/dev/null 2>&1 || fail "$tool wurde nicht gefunden."
done

# Alles, was zum Code gehoert und bei einem Deployment ersetzt wird. Was hier fehlt
# (.env, database/database.sqlite, storage/), bleibt auf dem Server unangetastet.
CODE_ITEMS="app bootstrap config lang public resources routes vendor database/migrations database/factories database/seeders
artisan composer.json composer.lock package.json package-lock.json vite.config.js README.md DEPLOYMENT.md
.env.production.example .editorconfig .gitattributes .gitignore REVISION"

# ----------------------------------------------------------------------------
#  Skripte, die auf dem Server laufen (werden per scp hochgeladen)
# ----------------------------------------------------------------------------
remote_common() {
    cat <<'COMMON'
#!/usr/bin/env bash
set -euo pipefail

log() { printf '  -> %s\n' "$*"; }

find_php() {
    local c
    for c in "${PHP_BIN:-}" /usr/local/php83/bin/php /usr/local/php84/bin/php /opt/plesk/php/8.3/bin/php \
        /opt/plesk/php/8.4/bin/php php8.3 php8.4 php; do
        [ -z "$c" ] && continue
        if command -v "$c" >/dev/null 2>&1 && "$c" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' 2>/dev/null; then
            echo "$c"
            return 0
        fi
    done
    return 1
}

PHP="$(find_php)" || { echo "[FEHLER] Kein PHP >= 8.2 gefunden. DEPLOY_PHP in .deploy.env setzen." >&2; exit 1; }
log "PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"
COMMON
}

remote_activate() {
    cat <<'ACTIVATE'
APP_REL="$1"
WORK_REL="$2"
RELEASE="$3"
KEEP="${KEEP_RELEASES:-3}"

APP="$(cd "$HOME/$APP_REL" && pwd)"
WORK="$(cd "$HOME/$WORK_REL" && pwd)"
STAGE="$WORK/stage-$RELEASE"
PREV="$WORK/previous/$RELEASE"
ARCHIVE="$WORK/uploads/lingolens-$RELEASE.tgz"

[ -f "$APP/.env" ] || { echo "[FEHLER] $APP/.env fehlt." >&2; exit 1; }
[ -f "$ARCHIVE" ] || { echo "[FEHLER] Archiv $ARCHIVE nicht gefunden." >&2; exit 1; }

log "Entpacke Release $RELEASE"
rm -rf "$STAGE"
mkdir -p "$STAGE" "$PREV" "$WORK/backups"
tar -xzf "$ARCHIVE" -C "$STAGE"
rm -f "$ARCHIVE"

log "Wartungsmodus an"
(cd "$APP" && "$PHP" artisan down --retry=15) || true

SWAPPED=""
restore() {
    echo "[FEHLER] Deployment abgebrochen - stelle den bisherigen Code wieder her." >&2
    for item in $SWAPPED; do
        rm -rf "${APP:?}/$item"
        [ -e "$PREV/$item" ] && mv "$PREV/$item" "$APP/$item"
    done
    (cd "$APP" && "$PHP" artisan optimize:clear >/dev/null 2>&1; "$PHP" artisan up >/dev/null 2>&1) || true
}
trap restore ERR

if [ -f "$APP/database/database.sqlite" ]; then
    log "Sichere die Datenbank"
    cp -p "$APP/database/database.sqlite" "$WORK/backups/database-$RELEASE.sqlite"
fi

log "Tausche den Code"
for item in $CODE_ITEMS; do
    mkdir -p "$(dirname "$PREV/$item")"
    if [ -e "$APP/$item" ]; then
        mv "$APP/$item" "$PREV/$item"
    fi
    SWAPPED="$SWAPPED $item"
    if [ -e "$STAGE/$item" ]; then
        mkdir -p "$(dirname "$APP/$item")"
        mv "$STAGE/$item" "$APP/$item"
    fi
done
rm -rf "$STAGE"

# Neuer bootstrap/-Ordner: Wartungsmodus dort weiterfuehren, Cache-Ordner anlegen.
mkdir -p "$APP/bootstrap/cache"

cd "$APP"
log "Package Discovery"
"$PHP" artisan package:discover --ansi

log "Migrationen"
"$PHP" artisan migrate --force

# Kein config:cache/route:cache/view:cache: die wuerden die Chroot-Pfade der SSH-Shell
# fest einbacken und beim Webserver mit open_basedir-Fehlern enden.
log "Caches leeren"
"$PHP" artisan optimize:clear
"$PHP" artisan event:cache

trap - ERR
"$PHP" artisan up
log "Release $RELEASE ist aktiv ($(cat "$APP/REVISION" 2>/dev/null || echo '?'))."

log "Behalte die letzten $KEEP Sicherungen"
(cd "$WORK/previous" && ls -1t | tail -n +"$((KEEP + 1))" | xargs -r rm -rf)
(cd "$WORK/backups" && ls -1t | tail -n +"$((KEEP * 3 + 1))" | xargs -r rm -f)
rm -f "$WORK/uploads/remote.sh"
ACTIVATE
}

remote_rollback() {
    cat <<'ROLLBACK'
APP_REL="$1"
WORK_REL="$2"

APP="$(cd "$HOME/$APP_REL" && pwd)"
WORK="$(cd "$HOME/$WORK_REL" && pwd)"
LAST="$(cd "$WORK/previous" 2>/dev/null && ls -1t | head -n 1)"
[ -n "$LAST" ] || { echo "[FEHLER] Kein vorheriger Code gefunden." >&2; exit 1; }
PREV="$WORK/previous/$LAST"

log "Stelle den Code vor Release $LAST wieder her (Datenbank bleibt, Migrationen werden nicht zurueckgerollt)"
(cd "$APP" && "$PHP" artisan down --retry=15) || true
for item in $CODE_ITEMS; do
    [ -e "$PREV/$item" ] || continue
    rm -rf "${APP:?}/$item"
    mkdir -p "$(dirname "$APP/$item")"
    mv "$PREV/$item" "$APP/$item"
done
rm -rf "$PREV"
mkdir -p "$APP/bootstrap/cache"
cd "$APP"
"$PHP" artisan package:discover --ansi
"$PHP" artisan optimize:clear
"$PHP" artisan up
log "Rollback fertig ($(cat "$APP/REVISION" 2>/dev/null || echo 'Revision unbekannt'))."
rm -f "$WORK/uploads/remote.sh"
ROLLBACK
}

# Laedt Gemeinsames + Skript hoch und fuehrt es mit Argumenten aus.
run_remote() {
    local script="$1"
    shift
    local file="$WORK_TMP/remote.sh"
    { remote_common; printf 'CODE_ITEMS="%s"\n' "$(echo $CODE_ITEMS)"; "$script"; } >"$file"
    ssh_run "mkdir -p '$WORKDIR/uploads'"
    scp_put "$file" "$WORKDIR/uploads/remote.sh"
    ssh_run "PHP_BIN='$DEPLOY_PHP' KEEP_RELEASES='$DEPLOY_KEEP' bash '$WORKDIR/uploads/remote.sh' $*"
}

WORK_TMP="$(mktemp -d)"
trap 'rm -rf "$WORK_TMP"' EXIT

probe_ssh() {
    if ! ssh -p "$DEPLOY_PORT" -o BatchMode=yes -o ConnectTimeout=10 "$REMOTE" true 2>/dev/null; then
        printf '\033[1;33mHinweis: SSH-Anmeldung ohne Passwort nicht moeglich - ohne Schluessel wird mehrfach nach dem Passwort gefragt.\033[0m\n'
    fi
}

check_online() {
    local status
    status="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$DEPLOY_URL/up" 2>/dev/null || true)"
    if [[ $status == 200 ]]; then
        printf '\033[1;32m%s ist online: %s\033[0m\n' "$1" "$DEPLOY_URL"
    else
        printf '\033[1;33m%s ist aktiv, %s/up antwortet aber mit "%s".\033[0m\n' "$1" "$DEPLOY_URL" "${status:-keine Antwort}"
    fi
}

# ----------------------------------------------------------------------------
#  Rollback
# ----------------------------------------------------------------------------
if [[ $MODE == rollback ]]; then
    step "Rollback auf $REMOTE"
    probe_ssh
    run_remote remote_rollback "'$APP'" "'$WORKDIR'"
    check_online "Vorheriger Code"
    exit 0
fi

# ----------------------------------------------------------------------------
#  1. Git-Stand und Qualitaetspruefung
# ----------------------------------------------------------------------------
step "1/5  Git-Stand pruefen"
branch="$(git rev-parse --abbrev-ref HEAD)"
[[ $branch == "$DEPLOY_BRANCH" ]] || fail "Aktueller Branch ist '$branch', deployt wird '$DEPLOY_BRANCH'."
if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    if [[ $ALLOW_DIRTY -eq 1 ]]; then
        printf '\033[1;33mHinweis: nicht committete Aenderungen werden NICHT mit deployt (nur HEAD).\033[0m\n'
    else
        fail "Es gibt nicht committete Aenderungen. Erst committen oder mit --allow-dirty (deployt trotzdem nur HEAD)."
    fi
fi
commit="$(git rev-parse --short HEAD)"
if [[ -n "$(git rev-list '@{u}..HEAD' 2>/dev/null || true)" ]]; then
    printf '\033[1;33mHinweis: HEAD ist noch nicht nach GitHub gepusht.\033[0m\n'
fi

if [[ $SKIP_TESTS -eq 0 ]]; then
    step "2/5  Qualitaetspruefung (Tests, Stil, statische Analyse)"
    php artisan test --compact
    ./vendor/bin/pint --test
    ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress
else
    step "2/5  Qualitaetspruefung uebersprungen (--skip-tests)"
fi

# ----------------------------------------------------------------------------
#  2. Release bauen
# ----------------------------------------------------------------------------
release="$(date +%Y%m%d-%H%M%S)-$commit"
work="$WORK_TMP/release"
archive="$WORK_TMP/lingolens-$release.tgz"
mkdir -p "$work"

step "3/5  Release $release bauen"
git archive --format=tar HEAD | tar -x -C "$work"

[[ -d node_modules ]] || npm ci --no-audit --no-fund
npm run build
cp -r public/build "$work/public/build"

(
    cd "$work"
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress
)

# Nur fuer die Entwicklung; .env und Datenbank kommen nie ins Release.
for p in tests UI_Mockups .claude deploy deploy.sh .deploy.env.example start.bat start-handy.bat stop.bat build-release.bat \
    phpunit.xml phpstan.neon CLAUDE.md lingolens-konzept.pdf lingolens-blueprint.md .env.example node_modules .env \
    database/database.sqlite; do
    rm -rf "${work:?}/$p"
done
printf '%s' "$commit" >"$work/REVISION"

tar -czf "$archive" -C "$work" .
echo "Release-Archiv: $(du -h "$archive" | cut -f1)"

if [[ $MODE == build ]]; then
    kept="${TMPDIR:-/tmp}/lingolens-$release.tgz"
    cp "$archive" "$kept"
    echo "Build-only: nichts hochgeladen. Archiv: $kept"
    exit 0
fi

# ----------------------------------------------------------------------------
#  3. Hochladen und aktivieren
# ----------------------------------------------------------------------------
step "4/5  Auf $DEPLOY_HOST hochladen"
probe_ssh
ssh_run "mkdir -p '$WORKDIR/uploads'"
scp_put "$archive" "$WORKDIR/uploads/lingolens-$release.tgz"

step "5/5  Release auf dem Server aktivieren"
run_remote remote_activate "'$APP'" "'$WORKDIR'" "'$release'"

step "Fertig"
check_online "Release $release ($commit)"
