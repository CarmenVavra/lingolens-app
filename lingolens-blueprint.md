# PROJEKT-BLUEPRINT: „LingoLens AI“ (AHS/BHS Englisch-Matura Coach)

Dieses Dokument dient als direkte Eingabe für **Claude-Design** (Erstellung des UI/UX-Frontends) und **Claude-Code** (Implementierung des Laravel-Backends).

---

## 1. Systemkonfiguration & API-Integration (Laravel Backend)

### `config/services.php`
```php
'google' => [
    'vision_key' => env('GOOGLE_VISION_API_KEY'),
],
'openai' => [
    'key' => env('OPENAI_API_KEY'),
],
```

### `.env`
```env
GOOGLE_VISION_API_KEY=dein_google_cloud_vision_api_key
OPENAI_API_KEY=dein_openai_api_key
QUEUE_CONNECTION=database
```

---

## 2. Datenbank-Schema & Migrationen

### Migration: `scans` Table
```php
Schema::create('scans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->text('raw_extracted_text')->nullable();
    $table->enum('school_type', ['AHS', 'BHS']);
    $table->string('department')->nullable(); // z.B. "Chemieingenieurwesen"
    $table->string('text_type'); // z.B. "Technical Report", "Essay"
    $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
    $table->timestamps();
});
```

### Migration: `feedbacks` Table
```php
Schema::create('feedbacks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('scan_id')->constrained()->onDelete('cascade');
    $table->text('ai_summary');
    $table->text('layout_feedback');
    $table->text('matura_tip');
    $table->timestamps();
});
```

### Migration: `corrections` Table
```php
Schema::create('corrections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('feedback_id')->constrained()->onDelete('cascade');
    $table->string('wrong_text');
    $table->string('correct_text');
    $table->enum('error_type', ['Grammar', 'Vocabulary', 'Style', 'Technical Term']);
    $table->text('explanation');
    $table->timestamps();
});
```

---

## 3. Asynchroner Queue-Job (`app/Jobs/ProcessTextScan.php`)

```php
namespace App\Jobs;

use App\Models\Scan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProcessTextScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Scan $scan, protected string $imagePath) {}

    public function handle(): void
    {
        try {
            $imageData = base64_encode(Storage::get($this->imagePath));

            // 1. Google Cloud Vision OCR
            $ocrResponse = Http::post('https://vision.googleapis.com/v1/images:annotate?key=' . config('services.google.vision_key'), [
                'requests' => [[
                    'image' => ['content' => $imageData],
                    'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']]
                ]]
            ]);

            $extractedText = $ocrResponse->json('responses.0.fullTextAnnotation.text') ?? '';
            $this->scan->update(['raw_extracted_text' => $extractedText]);

            // 2. OpenAI Prompt Engineering
            $prompt = "Du bist Englischlehrer an einer österreichischen Schule ({$this->scan->school_type})";
            if ($this->scan->school_type === 'BHS' && $this->scan->department) {
                $prompt .= " für die Fachrichtung {$this->scan->department}.";
            }
            $prompt .= " Korrigiere den Text auf B2 Matura-Niveau für die Textsorte '{$this->scan->text_type}'.";
            if ($this->scan->department === 'Chemieingenieurwesen') {
                $prompt .= " Achte strikt darauf, dass Alltagssprache durch chemische Fachbegriffe ersetzt wird (z.B. 'exothermic reaction', 'vacuum filtration'). Markiere dies als 'Technical Term'.";
            }
            $prompt .= " Antworte didaktisch auf Deutsch als valides JSON mit den Keys: 'summary', 'textsorten_check', 'matura_tipp', 'corrections' (Array mit original, corrected, type, explanation).";

            // 3. OpenAI API Aufruf
            $openAiResponse = Http::withToken(config('services.openai_key'))
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt],
                        ['role' => 'user', 'content' => $extractedText]
                    ]
                ]);

            $feedbackData = json_decode($openAiResponse->json('choices.0.message.content'), true);

            // 4. Datenbank-Persistenz
            $feedback = $this->scan->feedbacks()->create([
                'ai_summary' => $feedbackData['summary'] ?? '',
                'layout_feedback' => $feedbackData['textsorten_check'] ?? '',
                'matura_tip' => $feedbackData['matura_tipp'] ?? '',
            ]);

            if (isset($feedbackData['corrections']) && is_array($feedbackData['corrections'])) {
                foreach ($feedbackData['corrections'] as $correction) {
                    $feedback->corrections()->create([
                        'wrong_text' => $correction['original'],
                        'correct_text' => $correction['corrected'],
                        'error_type' => $correction['type'],
                        'explanation' => $correction['explanation'],
                    ]);
                }
            }

            $this->scan->update(['status' => 'completed']);
        } catch (\Exception $e) {
            $this->scan->update(['status' => 'failed']);
            throw $e;
        } finally {
            Storage::delete($this->imagePath);
        }
    }
}
```

---

## 4. Dashboard & Fehlerstatistiken (`app/Http/Controllers/DashboardController.php`)

```php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Correction;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $errorStats = Correction::whereHas('feedback.scan', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->select('error_type', DB::raw('count(*) as total'))
        ->groupBy('error_type')
        ->orderByDesc('total')
        ->get();

        return view('dashboard', compact('errorStats'));
    }
}
```

---

## 5. UI/UX & Frontend Spezifikationen (Für Claude-Design)

### Layout-Struktur (Tailwind CSS)
- **Dashboard:** Grid-System für Fehlerstatistiken (Farbcodierung: Grammatik = Rot, Vokabular = Blau, Stil = Gelb, Fachbegriff = Lila) sowie ein großer, zentrierter Upload-Button.
- **Weichen-Formular:** Dynamisches Formular. Wählt der User "BHS", blendet sich ein Dropdown für die Fachrichtung (*Chemieingenieurwesen*, etc.) ein. Die Textsorten-Auswahl ändert sich je nach Schultyp (AHS vs. BHS).
- **Split-Screen Korrektur-Modus:**
  - **Links (2/3):** Der Schülertext mit interaktiven HTML-Elementen. Fehlerhafte Passagen sind farblich passend hinterlegt.
  - **Rechts (1/3):** Ein dynamisches Seitenpaneel, das bei Klick auf ein fehlerhaftes Wort die genaue Erklärung und das richtige B2-Äquivalent anzeigt.

---

## 6. Referenz-Matrix für Chemieingenieurwesen (BHS)
- Alltagssprache: *the chemical reaction makes heat* $\rightarrow$ **exothermic reaction**
- Ungenauer Begriff: *clean the liquid with paper* $\rightarrow$ **vacuum filtration**
- Falsche Schreibweise: *distilate* $\rightarrow$ **fractional distillation**
