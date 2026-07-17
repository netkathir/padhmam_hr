<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBankAccount extends Model
{
    protected $fillable = [
        'employee_id',
        'account_holder_name',
        'bank_name',
        'branch_name',
        'account_number',
        'account_type',
        'ifsc_code',
        'is_primary',
        'status',
    ];

    protected $hidden = [
        'account_number',
    ];

    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'is_primary' => 'boolean',
        ];
    }

    protected function ifscCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    protected function accountHolderName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? trim($value) : null,
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function maskedAccountNumber(): ?string
    {
        if (! $this->account_number) {
            return null;
        }

        $length = strlen($this->account_number);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).substr($this->account_number, -4);
    }
}
