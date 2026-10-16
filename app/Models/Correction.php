<?php

namespace App\Models;

use App\Enums\ErrorType;
use Database\Factories\CorrectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single marked passage: wrong text → B2 equivalent plus explanation.
 *
 * @property int $id
 * @property int $feedback_id
 * @property string $wrong_text
 * @property string $correct_text
 * @property ErrorType $error_type
 * @property string $explanation
 * @property int|null $start_offset
 * @property int|null $end_offset
 * @property Carbon|null $understood_at
 */
class Correction extends Model
{
    /** @use HasFactory<CorrectionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'wrong_text',
        'correct_text',
        'error_type',
        'explanation',
        'start_offset',
        'end_offset',
        'understood_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'error_type' => ErrorType::class,
            'start_offset' => 'integer',
            'end_offset' => 'integer',
            'understood_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Feedback, $this>
     */
    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function isLocated(): bool
    {
        return $this->start_offset !== null && $this->end_offset !== null;
    }
}
