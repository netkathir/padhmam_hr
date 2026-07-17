<?php

namespace App\Models;

use App\Support\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Designation extends Model
{
    use HasFactory, BelongsToBranch;

    public const SCOPE_BRANCH = 'branch';
    public const SCOPE_DEPARTMENT = 'department';
    public const SCOPE_SECTION = 'section';

    public const SCOPE_LABELS = [
        self::SCOPE_BRANCH => 'Branch Level',
        self::SCOPE_DEPARTMENT => 'Department Level',
        self::SCOPE_SECTION => 'Section Level',
    ];

    protected $fillable = [
        'branch_id',
        'department_id',
        'section_id',
        'designation_code',
        'designation_name',
        'short_name',
        'description',
        'hierarchy_level',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'hierarchy_level' => 'integer',
            'display_order' => 'integer',
        ];
    }

    protected function designationCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    protected function designationName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? trim($value) : null,
        );
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('hierarchy_level IS NULL, hierarchy_level')
            ->orderBy('display_order')
            ->orderBy('designation_name');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeLevel(): string
    {
        if ($this->section_id) {
            return self::SCOPE_SECTION;
        }

        if ($this->department_id) {
            return self::SCOPE_DEPARTMENT;
        }

        return self::SCOPE_BRANCH;
    }

    public function scopeLevelLabel(): string
    {
        return self::SCOPE_LABELS[$this->scopeLevel()];
    }
}
