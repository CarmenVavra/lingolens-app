<?php

namespace App\Models;

use App\Enums\SchoolType;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $is_admin
 * @property SchoolType $school_type
 * @property string|null $school_form
 * @property string|null $department
 * @property string|null $class_name
 * @property int|null $grade
 * @property Carbon|null $privacy_accepted_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'school_type',
        'school_form',
        'department',
        'class_name',
        'grade',
        'privacy_accepted_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'school_type' => 'BHS',
        'is_admin' => false,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'school_type' => SchoolType::class,
            'grade' => 'integer',
            'privacy_accepted_at' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Scan, $this>
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * German e-mail with the reset link.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Invited students have not set their own password yet.
     */
    public function hasAcceptedInvitation(): bool
    {
        return $this->privacy_accepted_at !== null;
    }

    public function firstName(): string
    {
        return Str::before($this->name, ' ');
    }

    /**
     * Two-letter avatar, e.g. "Lena Gruber" → "LG".
     */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';

        return Str::upper(Str::substr($first, 0, 1).Str::substr($last, 0, 1));
    }

    /**
     * Hero tag, e.g. "BHS · HTL".
     */
    public function schoolLabel(): string
    {
        $type = $this->school_type->value;

        return $this->school_form && $this->school_form !== $type ? $type.' · '.$this->school_form : $type;
    }

    /**
     * Subtitle in the user chip, e.g. "5CHC · HTL".
     */
    public function chipLabel(): string
    {
        if ($this->isAdmin()) {
            return 'Administration';
        }

        return collect([$this->class_name, $this->school_form ?? $this->school_type->value])
            ->filter()
            ->implode(' · ');
    }

    /**
     * Scans left today under the cost limit (null = unlimited).
     */
    public function remainingScansToday(): ?int
    {
        $limit = (int) config('lingolens.daily_scan_limit');

        if ($limit <= 0) {
            return null;
        }

        return max(0, $limit - $this->scans()->where('created_at', '>=', now()->startOfDay())->count());
    }

    public function gradeLabel(): ?string
    {
        return $this->grade ? $this->school_type->gradeLabel($this->grade) : null;
    }
}
