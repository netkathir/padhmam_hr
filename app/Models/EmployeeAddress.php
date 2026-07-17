<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAddress extends Model
{
    public const TYPE_CURRENT = 'current';
    public const TYPE_PERMANENT = 'permanent';

    protected $fillable = [
        'employee_id',
        'address_type',
        'address_line_1',
        'address_line_2',
        'city',
        'district',
        'state',
        'country',
        'postal_code',
        'is_same_as_current',
    ];

    protected function casts(): array
    {
        return [
            'is_same_as_current' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('address_type', self::TYPE_CURRENT);
    }

    public function scopePermanent(Builder $query): Builder
    {
        return $query->where('address_type', self::TYPE_PERMANENT);
    }

    public function formatted(): string
    {
        return collect([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->district,
            $this->state,
            $this->postal_code,
            $this->country,
        ])->filter()->implode(', ');
    }
}
