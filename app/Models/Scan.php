<?php

namespace App\Models;

use App\Enums\ScanSource;
use App\Enums\ScanStatus;
use App\Enums\SchoolType;
use Database\Factories\ScanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A photographed text of a student and its processing state.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $title
 * @property string|null $raw_extracted_text
 * @property int $word_count
 * @property SchoolType $school_type
 * @property string|null $department
 * @property string $text_type
 * @property ScanSource $source
 * @property ScanStatus $status
 * @property int $progress_step
 * @property string|null $file_path
 * @property string|null $original_name
 * @property string|null $extraction_method
 * @property bool $uses_sample
 * @property string|null $failure_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Scan extends Model
{
    /** @use HasFactory<ScanFactory> */
    use HasFactory;

    /** Number of steps shown on the processing screen. */
    public const TOTAL_STEPS = 4;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'raw_extracted_text',
        'word_count',
        'school_type',
        'department',
        'text_type',
        'status',
        'progress_step',
        'source',
        'file_path',
        'original_name',
        'extraction_method',
        'uses_sample',
        'failure_reason',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'processing',
        'progress_step' => 1,
        'source' => 'photo',
        'word_count' => 0,
        'uses_sample' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'school_type' => SchoolType::class,
            'status' => ScanStatus::class,
            'source' => ScanSource::class,
            'progress_step' => 'integer',
            'word_count' => 'integer',
            'uses_sample' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Feedback, $this>
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * The feedback shown in the correction mode (the most recent one).
     *
     * @return HasOne<Feedback, $this>
     */
    public function latestFeedback(): HasOne
    {
        return $this->hasOne(Feedback::class)->latestOfMany();
    }

    /**
     * @return HasManyThrough<Correction, Feedback, $this>
     */
    public function corrections(): HasManyThrough
    {
        return $this->hasManyThrough(Correction::class, Feedback::class);
    }

    /**
     * @param  Builder<Scan>  $query
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', ScanStatus::Completed);
    }

    public function isCompleted(): bool
    {
        return $this->status === ScanStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === ScanStatus::Failed;
    }

    public function isProcessing(): bool
    {
        return $this->status === ScanStatus::Processing;
    }

    public function displayTitle(): string
    {
        return $this->title ?: $this->text_type;
    }

    /**
     * Tags in the correction header: [school_type] [department] [text_type].
     *
     * @return list<string>
     */
    public function metaTags(): array
    {
        return array_values(array_filter([$this->school_type->value, $this->department, $this->text_type]));
    }

    /**
     * Human date as in the design: "Heute, 08:14", "Gestern, 17:02", "Mo, 12. Okt".
     */
    public function displayDate(): string
    {
        $date = $this->created_at->copy()->locale('de');

        if ($date->isToday()) {
            return 'Heute, '.$date->format('H:i');
        }

        if ($date->isYesterday()) {
            return 'Gestern, '.$date->format('H:i');
        }

        $format = $date->isCurrentYear() ? 'dd, D. MMM' : 'dd, D. MMM YYYY';

        return rtrim($date->isoFormat($format), '.');
    }

    /**
     * Progress in percent for the processing bar.
     */
    public function progressPercent(): int
    {
        if ($this->isCompleted()) {
            return 100;
        }

        return (int) round(min($this->progress_step, self::TOTAL_STEPS) / self::TOTAL_STEPS * 100);
    }
}
