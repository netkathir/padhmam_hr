<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    public const TYPE_HEAD_OFFICE = 'head_office';
    public const TYPE_FACTORY = 'factory';
    public const TYPE_OFFICE = 'office';
    public const TYPE_WAREHOUSE = 'warehouse';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_HEAD_OFFICE => 'Head Office',
        self::TYPE_FACTORY => 'Factory',
        self::TYPE_OFFICE => 'Office',
        self::TYPE_WAREHOUSE => 'Warehouse',
        self::TYPE_OTHER => 'Other',
    ];

    protected $fillable = [
        'organization_id',
        'branch_code',
        'branch_name',
        'short_name',
        'branch_type',
        'is_head_office',
        'address_line_1',
        'address_line_2',
        'city',
        'district',
        'state',
        'country',
        'postal_code',
        'phone',
        'alternate_phone',
        'email',
        'contact_person_name',
        'contact_person_phone',
        'gstin',
        'pf_sub_code',
        'esi_sub_code',
        'professional_tax_number',
        'establishment_code',
        'timezone',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_head_office' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    protected function branchCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    protected function gstin(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeHeadOffice(Builder $query): Builder
    {
        return $query->where('is_head_office', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('branch_name');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isHeadOffice(): bool
    {
        return (bool) $this->is_head_office;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->branch_type] ?? ucfirst((string) $this->branch_type);
    }

    public function formattedAddress(): string
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

    public function activeUserCount(): int
    {
        return $this->users()->where('status', 'active')->count();
    }
}
