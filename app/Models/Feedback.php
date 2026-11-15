<?php

namespace App\Models;

use App\Enums\TaskFit;
use Database\Factories\FeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AI feedback for a scan: summary, text type check and Matura tip.
 *
 * @property int $id
 * @property int $scan_id
 * @property string $ai_summary
 * @property string $layout_feedback
 * @property string $matura_tip
 * @property TaskFit|null $task_fit
 * @property string|null $task_feedback
 * @property list<string>|null $task_tips
 * @property array{criteria: array<string, array{score: int|null, reason: string}>, total: int|null, max: int, word_count: int, required_words: int|null, word_penalty: bool, off_topic: bool}|null $rubric
 */
class Feedback extends Model
{
    /** @use HasFactory<FeedbackFactory> */
    use HasFactory;

    protected $table = 'feedbacks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ai_summary',
        'layout_feedback',
        'matura_tip',
        'task_fit',
        'task_feedback',
        'task_tips',
        'rubric',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'task_fit' => TaskFit::class,
            'task_tips' => 'array',
            'rubric' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Scan, $this>
     */
    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    /**
     * Corrections in reading order (unlocated ones last).
     *
     * @return HasMany<Correction, $this>
     */
    public function corrections(): HasMany
    {
        return $this->hasMany(Correction::class)
            ->orderByRaw('start_offset is null')
            ->orderBy('start_offset')
            ->orderBy('id');
    }
}
