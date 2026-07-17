<?php

namespace App\Models;

use App\Support\Traits\BelongsToBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeShiftAssignment extends Model
{
    use HasFactory, BelongsToBranch;

    public const TYPE_FIXED = 'fixed';
    public const TYPE_ROTATIONAL = 'rotational';
    public const TYPE_TEMPORARY = 'temporary';

    public const TYPES = [
        self::TYPE_FIXED => 'Fixed',
        self::TYPE_ROTATIONAL => 'Rotational',
        self::TYPE_TEMPORARY => 'Temporary',
    ];

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'branch_id',
        'employee_id',
        'shift_id',
        'assignment_type',
        'effective_from',
        'effective_to',
        'is_current',
        'assignment_reason',
        'change_reference',
        'status',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_current' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeFixed(Builder $query): Builder
    {
        return $query->where('assignment_type', self::TYPE_FIXED);
    }

    public function scopeRotational(Builder $query): Builder
    {
        return $query->where('assignment_type', self::TYPE_ROTATIONAL);
    }

    public function scopeTemporary(Builder $query): Builder
    {
        return $query->where('assignment_type', self::TYPE_TEMPORARY);
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForShift(Builder $query, int $shiftId): Builder
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopeEffectiveOn(Builder $query, string|Carbon $date): Builder
    {
        $date = $date instanceof Carbon ? $date->toDateString() : $date;

        return $query->where('effective_from', '<=', $date)
            ->where(fn (Builder $inner) => $inner->whereNull('effective_to')->orWhere('effective_to', '>=', $date));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('effective_from');
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFixed(): bool
    {
        return $this->assignment_type === self::TYPE_FIXED;
    }

    public function isRotational(): bool
    {
        return $this->assignment_type === self::TYPE_ROTATIONAL;
    }

    public function isTemporary(): bool
    {
        return $this->assignment_type === self::TYPE_TEMPORARY;
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

    /**
     * Date-derived status, ignoring the stored value — Cancelled is the one
     * status that is never recalculated from dates (spec section 18/34).
     * The scheduled sync command persists this; resolution logic must work
     * correctly even before that command has run.
     */
    public function computeStatus(?Carbon $today = null): string
    {
        if ($this->isCancelled()) {
            return self::STATUS_CANCELLED;
        }

        $today ??= now();

        if ($this->effective_from && $today->toDateString() < $this->effective_from->toDateString()) {
            return self::STATUS_SCHEDULED;
        }

        if ($this->effective_to && $today->toDateString() > $this->effective_to->toDateString()) {
            return self::STATUS_COMPLETED;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * Whether this assignment's period overlaps another [from, to] range.
     * An open-ended effective_to is treated as unbounded.
     */
    public function overlapsRange(string|Carbon $from, string|Carbon|null $to): bool
    {
        $from = $from instanceof Carbon ? $from : Carbon::parse($from);
        $to = $to ? ($to instanceof Carbon ? $to : Carbon::parse($to)) : null;

        $thisStartsBeforeOtherEnds = $to === null || $this->effective_from->lte($to);
        $otherStartsBeforeThisEnds = $this->effective_to === null || $from->lte($this->effective_to);

        return $thisStartsBeforeOtherEnds && $otherStartsBeforeThisEnds;
    }
}
