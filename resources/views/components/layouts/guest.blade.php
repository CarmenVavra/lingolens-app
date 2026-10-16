{{-- Login / registration: navy brand panel + form panel. --}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="de">
<head>
    <x-head :title="$title" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-page__brand">
            <span class="logo" aria-hidden="true">
                <span class="logo__mark"><span class="logo__ring"></span></span>
                <span class="logo__text">LingoLens <span>AI</span></span>
            </span>
            <div style="display:flex;flex-direction:column;gap:16px">
                <span class="kicker kicker--peach">Englisch-Matura Coach · AHS & BHS</span>
                <p class="auth-page__claim">Fotografieren. Verstehen. Matura schaffen.</p>
                <p style="font-size:16px;max-width:420px">
                    Fotografiere deinen handgeschriebenen Text – LingoLens korrigiert ihn auf B2-Niveau und erklärt jeden Fehler.
                </p>
            </div>
        </div>
        <main class="auth-page__form" id="main">
            <div class="auth-card">
                {{ $slot }}
                <x-legal-links />
            </div>
        </main>
    </div>
</body>
</html>
