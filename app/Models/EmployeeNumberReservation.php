<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeNumberReservation extends Model
{
    use HasFactory;

    public const STATUS_RESERVED = 'reserved';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'employee_number_rule_id',
        'branch_id',
        'employee_type_id',
        'sequence_period',
        'serial_number',
        'generated_employee_number',
        'reservation_token',
        'reserved_by',
        'reserved_at',
        'expires_at',
        'finalized_at',
        'cancelled_at',
        'status',
    ];

    protected $hidden = [
        'reservation_token',
    ];

    protected function casts(): array
    {
        return [
            'serial_number' => 'integer',
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'finalized_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(EmployeeNumberRule::class, 'employee_number_rule_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employeeType(): BelongsTo
    {
        return $this->belongsTo(EmployeeType::class);
    }

    public function reservedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESERVED);
    }

    public function scopeActiveHold(Builder $query): Builder
    {
        return $query->reserved()->where(fn (Builder $inner) => $inner->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isExpired(): bool
    {
        return (bool) $this->expires_at && $this->expires_at->isPast() && $this->isReserved();
    }
}
