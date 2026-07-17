<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeType extends Model
{
    use HasFactory;

    public const STAFF = 'STAFF';

    public const COMPANY_LABOUR = 'COMPANY_LABOUR';

    public const CONTRACT_LABOUR = 'CONTRACT_LABOUR';

    public const SHIFT_FIXED = 'fixed';

    public const SHIFT_ROTATIONAL = 'rotational';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_system',
        'requires_contractor',
        'attendance_applicable',
        'leave_applicable',
        'payroll_applicable',
        'overtime_applicable',
        'default_shift_type',
        'employee_number_prefix',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'requires_contractor' => 'boolean',
            'attendance_applicable' => 'boolean',
            'leave_applicable' => 'boolean',
            'payroll_applicable' => 'boolean',
            'overtime_applicable' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? trim($value) : null,
        );
    }

    protected function employeeNumberPrefix(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeFixedShift(Builder $query): Builder
    {
        return $query->where('default_shift_type', self::SHIFT_FIXED);
    }

    public function scopeRotationalShift(Builder $query): Builder
    {
        return $query->where('default_shift_type', self::SHIFT_ROTATIONAL);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isStaff(): bool
    {
        return $this->code === self::STAFF;
    }

    public function isCompanyLabour(): bool
    {
        return $this->code === self::COMPANY_LABOUR;
    }

    public function isContractLabour(): bool
    {
        return $this->code === self::CONTRACT_LABOUR;
    }

    public function requiresContractor(): bool
    {
        return (bool) $this->requires_contractor;
    }

    public function usesFixedShiftByDefault(): bool
    {
        return $this->default_shift_type === self::SHIFT_FIXED;
    }

    public function usesRotationalShiftByDefault(): bool
    {
        return $this->default_shift_type === self::SHIFT_ROTATIONAL;
    }
}
