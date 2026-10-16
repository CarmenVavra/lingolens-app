# ================================================================
#  LingoLens AI - baut ein Upload-Paket fuer Shared Hosting (netcup)
#
#  Ergebnis: build\lingolens-release.zip
#  Enthaelt: committeten Code, vendor/ ohne Dev-Pakete, gebaute Assets.
#  Enthaelt NICHT: .env, Datenbank, Uploads, Tests, Mockups.
# ================================================================
$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$out = Join-Path $root 'build'
$stage = Join-Path $out 'lingolens'
$zip = Join-Path $out 'lingolens-release.zip'

Set-Location $root

if (git status --porcelain) {
    Write-Warning 'Es gibt nicht committete Aenderungen. Ins Paket kommt nur der letzte Commit.'
}

Write-Host '[1/5] Arbeitsordner vorbereiten ...'
if (Test-Path $out) { Remove-Item -Recurse -Force $out }
New-Item -ItemType Directory -Force $stage | Out-Null

Write-Host '[2/5] Code aus dem letzten Commit exportieren ...'
# Ueber eine Datei statt einer Pipe: PowerShell 5.1 veraendert Binaerdaten in Pipes.
$tarball = Join-Path $out 'source.tar'
git archive --format=tar -o "$tarball" HEAD
if ($LASTEXITCODE -ne 0) { throw 'git archive fehlgeschlagen.' }
tar.exe -x -f "$tarball" -C "$stage"
if ($LASTEXITCODE -ne 0) { throw 'Entpacken fehlgeschlagen.' }
Remove-Item $tarball

# Nur fuer die Entwicklung benoetigt
$devOnly = @('tests', 'UI_Mockups', '.claude', 'deploy', 'start.bat', 'stop.bat', 'build-release.bat',
    'phpunit.xml', 'phpstan.neon', 'CLAUDE.md', 'lingolens-konzept.pdf', 'lingolens-blueprint.md', '.env.example')
foreach ($item in $devOnly) {
    $path = Join-Path $stage $item
    if (Test-Path $path) { Remove-Item -Recurse -Force $path }
}

Write-Host '[3/5] PHP-Abhaengigkeiten ohne Dev-Pakete installieren ...'
composer install --no-dev --optimize-autoloader --no-interaction --no-progress --working-dir "$stage"
if ($LASTEXITCODE -ne 0) { throw 'composer install fehlgeschlagen.' }

Write-Host '[4/5] Frontend-Assets bauen ...'
npm ci --no-audit --no-fund
if ($LASTEXITCODE -ne 0) { throw 'npm ci fehlgeschlagen.' }
npm run build
if ($LASTEXITCODE -ne 0) { throw 'npm run build fehlgeschlagen.' }
Copy-Item -Recurse (Join-Path $root 'public\build') (Join-Path $stage 'public\build')

Write-Host '[5/5] ZIP erstellen ...'
# tar.exe (Windows 10+) schreibt Pfade mit "/" - wichtig fuer das Entpacken unter Linux.
tar.exe -a -c -f "$zip" -C "$stage" .
if ($LASTEXITCODE -ne 0) { throw 'ZIP erstellen fehlgeschlagen.' }

$size = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host ''
Write-Host "Fertig: $zip ($size MB)" -ForegroundColor Green
Write-Host 'Weiter mit DEPLOYMENT.md, Schritt 3.'
