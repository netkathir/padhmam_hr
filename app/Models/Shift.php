<?php

namespace App\Models;

use App\Support\Traits\BelongsToBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shift extends Model
{
    use HasFactory, BelongsToBranch;

    public const TYPE_FIXED = 'fixed';
    public const TYPE_ROTATIONAL = 'rotational';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const DAY_CODES = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];

    /**
     * Once a Shift has operational usage (see hasOperationalUsage()), these
     * fields must not be freely edited — a new Shift version should be
     * created instead. Enforced by UpdateShiftRequest and re-asserted in
     * ShiftService.
     */
    public const CRITICAL_FIELDS = [
        'shift_type',
        'start_time',
        'end_time',
        'break_duration_minutes',
        'early_entry_allowed_minutes',
        'late_entry_grace_minutes',
        'early_exit_grace_minutes',
        'late_exit_allowed_minutes',
        'minimum_half_day_minutes',
        'minimum_full_day_minutes',
        'overtime_applicable',
        'overtime_start_after_minutes',
        'effective_from',
    ];

    protected $fillable = [
        'branch_id',
        'shift_code',
        'shift_name',
        'short_name',
        'shift_type',
        'start_time',
        'end_time',
        'is_overnight',
        'gross_shift_minutes',
        'break_duration_minutes',
        'scheduled_work_minutes',
        'early_entry_allowed_minutes',
        'late_entry_grace_minutes',
        'early_exit_grace_minutes',
        'late_exit_allowed_minutes',
        'minimum_half_day_minutes',
        'minimum_full_day_minutes',
        'overtime_applicable',
        'overtime_start_after_minutes',
        'applicable_days',
        'effective_from',
        'effective_to',
        'color_code',
        'description',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_overnight' => 'boolean',
            'gross_shift_minutes' => 'integer',
            'break_duration_minutes' => 'integer',
            'scheduled_work_minutes' => 'integer',
            'early_entry_allowed_minutes' => 'integer',
            'late_entry_grace_minutes' => 'integer',
            'early_exit_grace_minutes' => 'integer',
            'late_exit_allowed_minutes' => 'integer',
            'minimum_half_day_minutes' => 'integer',
            'minimum_full_day_minutes' => 'integer',
            'overtime_applicable' => 'boolean',
            'overtime_start_after_minutes' => 'integer',
            'applicable_days' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'display_order' => 'integer',
        ];
    }

    protected function shiftCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    protected function shiftName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? trim($value) : null,
        );
    }

    protected function shortName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? trim($value) : null,
        );
    }

    protected function colorCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    /**
     * Stored as a normalized "H:i:s" string (never trusted as a full
     * datetime, to avoid MySQL TIME-column ambiguity). Read access returns a
     * Carbon instance anchored to today's date — only the time-of-day part
     * is meaningful for a Shift.
     */
    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?Carbon => $value !== null ? Carbon::createFromFormat('H:i:s', $value) : null,
            set: fn ($value): ?string => self::normalizeTimeInput($value),
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?Carbon => $value !== null ? Carbon::createFromFormat('H:i:s', $value) : null,
            set: fn ($value): ?string => self::normalizeTimeInput($value),
        );
    }

    private static function normalizeTimeInput(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        return Carbon::parse($value)->format('H:i:s');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employeeTypes(): BelongsToMany
    {
        return $this->belongsToMany(EmployeeType::class, 'shift_employee_types')
            ->using(ShiftEmployeeType::class)
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeFixed(Builder $query): Builder
    {
        return $query->where('shift_type', self::TYPE_FIXED);
    }

    public function scopeRotational(Builder $query): Builder
    {
        return $query->where('shift_type', self::TYPE_ROTATIONAL);
    }

    public function scopeDayShift(Builder $query): Builder
    {
        return $query->where('is_overnight', false);
    }

    public function scopeOvernight(Builder $query): Builder
    {
        return $query->where('is_overnight', true);
    }

    public function scopeEffectiveOn(Builder $query, string|Carbon $date): Builder
    {
        $date = $date instanceof Carbon ? $date->toDateString() : $date;

        return $query->where('effective_from', '<=', $date)
            ->where(fn (Builder $inner) => $inner->whereNull('effective_to')->orWhere('effective_to', '>=', $date));
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->active()->effectiveOn(now());
    }

    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('effective_from', '>', now()->toDateString());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('effective_to')->where('effective_to', '<', now()->toDateString());
    }

    public function scopeForEmployeeType(Builder $query, int $employeeTypeId): Builder
    {
        return $query->whereHas('employeeTypes', fn (Builder $inner) => $inner->where('employee_types.id', $employeeTypeId));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('shift_name');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isFixed(): bool
    {
        return $this->shift_type === self::TYPE_FIXED;
    }

    public function isRotational(): bool
    {
        return $this->shift_type === self::TYPE_ROTATIONAL;
    }

    public function isOvernight(): bool
    {
        return (bool) $this->is_overnight;
    }

    /**
     * Pure calculation, independent of stored state — used both to derive
     * the persisted gross_shift_minutes and to validate submitted timing
     * before save (see ShiftTimingService, which wraps this identically so
     * the math is never duplicated).
     */
    public static function calculateIsOvernight(Carbon $start, Carbon $end): bool
    {
        return self::minutesOfDay($end) <= self::minutesOfDay($start);
    }

    public static function calculateGrossMinutes(Carbon $start, Carbon $end): int
    {
        $startMinutes = self::minutesOfDay($start);
        $endMinutes = self::minutesOfDay($end);

        if ($endMinutes <= $startMinutes) {
            return (24 * 60 - $startMinutes) + $endMinutes;
        }

        return $endMinutes - $startMinutes;
    }

    public static function calculateScheduledWorkMinutes(int $grossMinutes, int $breakMinutes): int
    {
        return $grossMinutes - $breakMinutes;
    }

    private static function minutesOfDay(Carbon $time): int
    {
        return ($time->hour * 60) + $time->minute;
    }

    public function formattedGrossDuration(): string
    {
        return self::formatMinutes($this->gross_shift_minutes);
    }

    public function formattedWorkDuration(): string
    {
        return self::formatMinutes($this->scheduled_work_minutes);
    }

    public function formattedBreakDuration(): string
    {
        return self::formatMinutes($this->break_duration_minutes);
    }

    public static function formatMinutes(?int $minutes): string
    {
        $minutes ??= 0;
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        return sprintf('%dh %02dm', $hours, $remainder);
    }

    public function isEffectiveOn(string|Carbon $date): bool
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        if ($this->effective_from && $date->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_to && $date->gt($this->effective_to)) {
            return false;
        }

        return true;
    }

    public function supportsEmployeeType(EmployeeType|int $employeeType): bool
    {
        $employeeTypeId = $employeeType instanceof EmployeeType ? $employeeType->id : $employeeType;

        return $this->employeeTypes->contains('id', $employeeTypeId);
    }

    public function isApplicableOnDay(string $dayCode): bool
    {
        return in_array(strtoupper($dayCode), $this->applicable_days ?? [], true);
    }

    /**
     * Always false in this module — no Employee or Attendance tables exist
     * yet. Once Employee Shift Assignment and Attendance Processing are
     * implemented, this method must be extended to check for:
     *  - active employee fixed-shift assignments referencing this Shift
     *  - active rotational Shift assignments referencing this Shift
     *  - unprocessed/open attendance records referencing this Shift
     * Until then, critical timing fields remain freely editable.
     */
    public function hasOperationalUsage(): bool
    {
        return false;
    }

    /**
     * Display-only lifecycle/effective state, mirroring the pattern used by
     * EmployeeNumberRule::effectivePeriodStatus().
     */
    public function validityState(): string
    {
        if ($this->isDraft()) {
            return 'Draft';
        }

        if ($this->isInactive()) {
            return 'Inactive';
        }

        $today = now()->toDateString();

        if ($this->effective_from && $this->effective_from->toDateString() > $today) {
            return 'Upcoming';
        }

        if ($this->effective_to && $this->effective_to->toDateString() < $today) {
            return 'Expired';
        }

        return 'Current';
    }

    /**
     * Attendance-window foundation (spec section 34): the window a future
     * biometric punch would need to fall within to be associated with this
     * Shift on the given reference date. For overnight Shifts the window
     * crosses midnight, so the end boundary is anchored to the day after
     * $referenceDate.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public function attendanceWindowFor(string|Carbon $referenceDate): array
    {
        $referenceDate = $referenceDate instanceof Carbon ? $referenceDate->copy()->startOfDay() : Carbon::parse($referenceDate)->startOfDay();

        $start = $referenceDate->copy()
            ->addHours($this->start_time->hour)
            ->addMinutes($this->start_time->minute)
            ->subMinutes($this->early_entry_allowed_minutes);

        $endDay = $this->isOvernight() ? $referenceDate->copy()->addDay() : $referenceDate->copy();

        $end = $endDay
            ->addHours($this->end_time->hour)
            ->addMinutes($this->end_time->minute)
            ->addMinutes($this->late_exit_allowed_minutes);

        return ['start' => $start, 'end' => $end];
    }
}
