<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contractor extends Model
{
    use HasFactory;

    public const TYPES = [
        'individual' => 'Individual',
        'proprietorship' => 'Proprietorship',
        'partnership' => 'Partnership',
        'company' => 'Company',
        'other' => 'Other',
    ];

    protected $fillable = [
        'organization_id',
        'contractor_code',
        'legal_name',
        'trade_name',
        'contractor_type',
        'contact_person_name',
        'primary_phone',
        'alternate_phone',
        'primary_email',
        'address_line_1',
        'address_line_2',
        'city',
        'district',
        'state',
        'country',
        'postal_code',
        'pan_number',
        'gstin',
        'pf_registration_number',
        'esi_registration_number',
        'labour_licence_number',
        'labour_licence_valid_from',
        'labour_licence_valid_to',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'labour_licence_valid_from' => 'date',
            'labour_licence_valid_to' => 'date',
        ];
    }

    protected function contractorCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    protected function legalName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? trim($value) : null,
        );
    }

    protected function tradeName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? trim($value) : null,
        );
    }

    protected function panNumber(): Attribute
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

    protected function pfRegistrationNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? trim($value) : null,
        );
    }

    protected function esiRegistrationNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? trim($value) : null,
        );
    }

    protected function labourLicenceNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? trim($value) : null,
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branchEngagements(): HasMany
    {
        return $this->hasMany(ContractorBranchEngagement::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContractorDocument::class);
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

    /**
     * withoutGlobalScopes() is required here because ContractorBranchEngagement
     * is branch-scoped: without it, this constraint would silently be
     * re-filtered down to whatever branch is currently active, rather than
     * the requested $branchId.
     */
    public function scopeAssignedToBranch(Builder $query, int $branchId): Builder
    {
        return $query->whereHas('branchEngagements', fn (Builder $inner) => $inner->withoutGlobalScopes()->where('branch_id', $branchId));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('legal_name');
    }

    public function scopeLicenceExpired(Builder $query): Builder
    {
        return $query->whereNotNull('labour_licence_valid_to')->where('labour_licence_valid_to', '<', now()->toDateString());
    }

    public function scopeLicenceExpiringSoon(Builder $query, ?int $days = null): Builder
    {
        $days ??= (int) config('hrms.contractor_licence_expiry_warning_days', 30);
        $today = now()->toDateString();
        $threshold = now()->addDays($days)->toDateString();

        return $query->whereNotNull('labour_licence_valid_to')->whereBetween('labour_licence_valid_to', [$today, $threshold]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * A Contractor is only selectable for future Contract Labour registration
     * once it is active and has at least one active branch engagement.
     */
    public function hasActiveBranchEngagement(): bool
    {
        return $this->branchEngagements()->where('status', 'active')->exists();
    }

    public function isLicenceExpired(): bool
    {
        return (bool) $this->labour_licence_valid_to && $this->labour_licence_valid_to->isPast();
    }

    public function isLicenceExpiringSoon(?int $days = null): bool
    {
        if (! $this->labour_licence_valid_to || $this->isLicenceExpired()) {
            return false;
        }

        $days ??= (int) config('hrms.contractor_licence_expiry_warning_days', 30);

        return now()->diffInDays($this->labour_licence_valid_to, false) <= $days;
    }

    public function licenceValidityLabel(): string
    {
        if (! $this->labour_licence_valid_to) {
            return 'Not Set';
        }

        if ($this->isLicenceExpired()) {
            return 'Expired';
        }

        if ($this->isLicenceExpiringSoon()) {
            return 'Expiring Soon';
        }

        return 'Active';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->contractor_type] ?? ucfirst((string) $this->contractor_type);
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
