<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_code',
        'legal_name',
        'display_name',
        'business_type',
        'incorporation_date',
        'financial_year_start_month',
        'address_line_1',
        'address_line_2',
        'city',
        'district',
        'state',
        'country',
        'postal_code',
        'primary_phone',
        'alternate_phone',
        'primary_email',
        'website',
        'pan_number',
        'tan_number',
        'gstin',
        'pf_registration_number',
        'esi_registration_number',
        'professional_tax_number',
        'logo_path',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'incorporation_date' => 'date',
            'financial_year_start_month' => 'integer',
        ];
    }

    protected function organizationCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    protected function panNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    protected function tanNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    protected function gstin(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function headOfficeBranch(): HasOne
    {
        return $this->hasOne(Branch::class)->where('is_head_office', true);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public static function maskStatutoryNumber(?string $value): ?string
    {
        if (! $value) {
            return $value;
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).substr($value, -4);
    }
}
