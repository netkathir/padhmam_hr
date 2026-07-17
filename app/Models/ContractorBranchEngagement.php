<?php

namespace App\Models;

use App\Support\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractorBranchEngagement extends Model
{
    use HasFactory, BelongsToBranch;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'contractor_id',
        'branch_id',
        'agreement_number',
        'agreement_date',
        'contract_start_date',
        'contract_end_date',
        'maximum_labour_count',
        'branch_labour_licence_number',
        'branch_licence_valid_from',
        'branch_licence_valid_to',
        'contact_person_name',
        'contact_person_phone',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'agreement_date' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'branch_licence_valid_from' => 'date',
            'branch_licence_valid_to' => 'date',
            'maximum_labour_count' => 'integer',
        ];
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    public function scopeWithValidContract(Builder $query, ?string $date = null): Builder
    {
        $date ??= now()->toDateString();

        return $query->where('contract_start_date', '<=', $date)
            ->where(fn (Builder $inner) => $inner->whereNull('contract_end_date')->orWhere('contract_end_date', '>=', $date));
    }

    public function scopeExpiredContract(Builder $query, ?string $date = null): Builder
    {
        $date ??= now()->toDateString();

        return $query->whereNotNull('contract_end_date')->where('contract_end_date', '<', $date);
    }

    public function scopeExpiringSoon(Builder $query, ?int $days = null): Builder
    {
        $days ??= (int) config('hrms.contractor_licence_expiry_warning_days', 30);
        $today = now()->toDateString();
        $threshold = now()->addDays($days)->toDateString();

        return $query->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [$today, $threshold]);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('contract_start_date');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isContractUpcoming(): bool
    {
        return $this->contract_start_date && $this->contract_start_date->isFuture();
    }

    public function isContractExpired(): bool
    {
        return (bool) $this->contract_end_date && $this->contract_end_date->isPast();
    }

    public function isContractExpiringSoon(?int $days = null): bool
    {
        if (! $this->contract_end_date || $this->isContractExpired()) {
            return false;
        }

        $days ??= (int) config('hrms.contractor_licence_expiry_warning_days', 30);

        return now()->diffInDays($this->contract_end_date, false) <= $days;
    }

    /**
     * Display-only validity state for the contract period. "Expiring Soon"
     * and "Expired" are calculated states, not stored statuses.
     */
    public function contractValidityStatus(): string
    {
        if (! $this->isActive()) {
            return 'Inactive';
        }

        if ($this->isContractUpcoming()) {
            return 'Upcoming';
        }

        if ($this->isContractExpired()) {
            return 'Expired';
        }

        if ($this->isContractExpiringSoon()) {
            return 'Expiring Soon';
        }

        return 'Active';
    }

    /**
     * The branch-specific licence takes precedence; falls back to the
     * Contractor-level general licence when no branch-specific licence
     * has been entered.
     */
    public function effectiveLicenceNumber(): ?string
    {
        return $this->branch_labour_licence_number ?: $this->contractor?->labour_licence_number;
    }

    public function effectiveLicenceValidFrom(): mixed
    {
        return $this->branch_labour_licence_number
            ? $this->branch_licence_valid_from
            : $this->contractor?->labour_licence_valid_from;
    }

    public function effectiveLicenceValidTo(): mixed
    {
        return $this->branch_labour_licence_number
            ? $this->branch_licence_valid_to
            : $this->contractor?->labour_licence_valid_to;
    }

    public function isLicenceExpired(): bool
    {
        $validTo = $this->effectiveLicenceValidTo();

        return (bool) $validTo && $validTo->isPast();
    }

    public function isLicenceExpiringSoon(?int $days = null): bool
    {
        $validTo = $this->effectiveLicenceValidTo();

        if (! $validTo || $this->isLicenceExpired()) {
            return false;
        }

        $days ??= (int) config('hrms.contractor_licence_expiry_warning_days', 30);

        return now()->diffInDays($validTo, false) <= $days;
    }

    public function hasValidLicenceForDate(string|\DateTimeInterface $date): bool
    {
        $validFrom = $this->effectiveLicenceValidFrom();
        $validTo = $this->effectiveLicenceValidTo();

        if (! $validFrom && ! $validTo) {
            return true;
        }

        $date = $date instanceof \DateTimeInterface ? \Illuminate\Support\Carbon::instance($date) : \Illuminate\Support\Carbon::parse($date);

        if ($validFrom && $date->lt($validFrom)) {
            return false;
        }

        if ($validTo && $date->gt($validTo)) {
            return false;
        }

        return true;
    }

    /**
     * Full validity check for future Contract Labour registration. Employee
     * capacity checks against maximum_labour_count will be enforced once the
     * Employee Registration module exists and active labour counts can be
     * computed.
     */
    public function isValidForEmployeeAssignment(string|\DateTimeInterface|null $joiningDate = null): bool
    {
        $joiningDate ??= now();

        if (! $this->contractor?->isActive() || ! $this->isActive()) {
            return false;
        }

        if (! $this->branch?->isActive()) {
            return false;
        }

        $joiningDate = $joiningDate instanceof \DateTimeInterface ? \Illuminate\Support\Carbon::instance($joiningDate) : \Illuminate\Support\Carbon::parse($joiningDate);

        if ($this->contract_start_date && $joiningDate->lt($this->contract_start_date)) {
            return false;
        }

        if ($this->contract_end_date && $joiningDate->gt($this->contract_end_date)) {
            return false;
        }

        return $this->hasValidLicenceForDate($joiningDate);
    }
}
