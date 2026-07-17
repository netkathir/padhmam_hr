<?php

namespace App\Models;

use App\Support\Traits\BelongsToBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeNumberRule extends Model
{
    use HasFactory, BelongsToBranch;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const RESET_NEVER = 'never';
    public const RESET_YEARLY = 'yearly';
    public const RESET_FINANCIAL_YEARLY = 'financial_yearly';

    public const YEAR_FORMAT_YY = 'YY';
    public const YEAR_FORMAT_YYYY = 'YYYY';

    public const SEPARATORS = ['-', '/', '_', ''];

    public const SEQUENCE_PERIOD_GLOBAL = 'GLOBAL';

    /**
     * Fields that must not change once this rule has issued at least one
     * Employee Number (see hasIssuedNumbers()). Enforced by
     * UpdateEmployeeNumberRuleRequest and re-asserted in the service layer.
     */
    public const CRITICAL_FIELDS = [
        'employee_type_id',
        'prefix',
        'include_branch_code',
        'include_employee_type_prefix',
        'employee_type_prefix',
        'include_year',
        'year_format',
        'separator',
        'serial_number_length',
        'starting_number',
        'reset_frequency',
        'effective_from',
    ];

    protected $fillable = [
        'branch_id',
        'employee_type_id',
        'rule_name',
        'prefix',
        'include_branch_code',
        'include_employee_type_prefix',
        'employee_type_prefix',
        'include_year',
        'year_format',
        'separator',
        'serial_number_length',
        'starting_number',
        'reset_frequency',
        'effective_from',
        'effective_to',
        'is_default',
        'status',
        'description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'include_branch_code' => 'boolean',
            'include_employee_type_prefix' => 'boolean',
            'include_year' => 'boolean',
            'serial_number_length' => 'integer',
            'starting_number' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_default' => 'boolean',
        ];
    }

    protected function ruleName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? trim($value) : null,
        );
    }

    protected function prefix(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    protected function employeeTypePrefix(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employeeType(): BelongsTo
    {
        return $this->belongsTo(EmployeeType::class);
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(EmployeeNumberSequence::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(EmployeeNumberReservation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForEmployeeType(Builder $query, int $employeeTypeId): Builder
    {
        return $query->where('employee_type_id', $employeeTypeId);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeEffectiveOn(Builder $query, string|Carbon $date): Builder
    {
        $date = $date instanceof Carbon ? $date->toDateString() : $date;

        return $query->where('effective_from', '<=', $date)
            ->where(fn (Builder $inner) => $inner->whereNull('effective_to')->orWhere('effective_to', '>=', $date));
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->active()->effectiveOn(now());
    }

    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('effective_from', '>', now()->toDateString());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('effective_to')->where('effective_to', '<', now()->toDateString());
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('effective_from');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Whether this rule has ever issued a real Employee Number. Once true,
     * critical formatting fields become immutable (see CRITICAL_FIELDS) and
     * a "Create New Version" must be used instead of editing in place.
     */
    public function hasIssuedNumbers(): bool
    {
        return $this->sequences()->where('last_issued_number', '>', 0)->exists();
    }

    /**
     * Display-only effective/lifecycle state. Draft and Inactive map
     * directly to the stored status; Active rules are further broken down
     * by date into Upcoming / Active / Expired for the UI badge.
     */
    public function effectivePeriodStatus(): string
    {
        if ($this->isDraft()) {
            return 'Draft';
        }

        if ($this->isInactive()) {
            return 'Inactive';
        }

        $today = now()->toDateString();

        if ($this->effective_from && $this->effective_from->toDateString() > $today) {
            return 'Upcoming';
        }

        if ($this->effective_to && $this->effective_to->toDateString() < $today) {
            return 'Expired';
        }

        return 'Active';
    }

    public function maxSerialValue(): int
    {
        return (10 ** $this->serial_number_length) - 1;
    }

    /**
     * Resolves the sequence-period key for a given date under this rule's
     * reset frequency. "Never" always resolves to the GLOBAL period; Yearly
     * resets on the calendar year; Financial Yearly resets on the
     * organization's configured financial-year start month.
     */
    public function sequencePeriodFor(Carbon $date, Organization $organization): string
    {
        return match ($this->reset_frequency) {
            self::RESET_YEARLY => (string) $date->year,
            self::RESET_FINANCIAL_YEARLY => $this->financialYearPeriod($date, (int) $organization->financial_year_start_month),
            default => self::SEQUENCE_PERIOD_GLOBAL,
        };
    }

    private function financialYearPeriod(Carbon $date, int $startMonth): string
    {
        $startYear = $date->month >= $startMonth ? $date->year : $date->year - 1;
        $endYearShort = str_pad((string) (($startYear + 1) % 100), 2, '0', STR_PAD_LEFT);

        return "FY{$startYear}-{$endYearShort}";
    }

    /**
     * Component order is fixed: Prefix, Branch Code, Employee Type Prefix,
     * Year, Serial Number. Only enabled, non-empty components are included
     * so the separator never produces leading/trailing/duplicate gaps.
     */
    public function buildComponents(int $serial, Carbon $date, Branch $branch, string $sequencePeriod): array
    {
        $components = [];

        if ($this->prefix) {
            $components[] = $this->prefix;
        }

        if ($this->include_branch_code && $branch->branch_code) {
            $components[] = $branch->branch_code;
        }

        if ($this->include_employee_type_prefix && $this->employee_type_prefix) {
            $components[] = $this->employee_type_prefix;
        }

        if ($this->include_year) {
            $components[] = $this->yearComponent($date, $sequencePeriod);
        }

        $components[] = str_pad((string) $serial, $this->serial_number_length, '0', STR_PAD_LEFT);

        return $components;
    }

    public function buildNumber(int $serial, Carbon $date, Branch $branch, string $sequencePeriod): string
    {
        return implode($this->separator ?? '', $this->buildComponents($serial, $date, $branch, $sequencePeriod));
    }

    /**
     * Ties the year text to the resolved sequence period's start year rather
     * than the raw generation date, so the number stays internally
     * consistent with its period even when generated near a boundary.
     */
    private function yearComponent(Carbon $date, string $sequencePeriod): string
    {
        $year = $date->year;

        if ($this->reset_frequency === self::RESET_FINANCIAL_YEARLY && str_starts_with($sequencePeriod, 'FY')) {
            $year = (int) substr($sequencePeriod, 2, 4);
        } elseif ($this->reset_frequency === self::RESET_YEARLY && ctype_digit($sequencePeriod)) {
            $year = (int) $sequencePeriod;
        }

        return $this->year_format === self::YEAR_FORMAT_YY
            ? str_pad((string) ($year % 100), 2, '0', STR_PAD_LEFT)
            : (string) $year;
    }

    /**
     * Resolves the single applicable active rule for a Branch + Employee
     * Type as of a given date (normally the employee's joining date, not
     * necessarily "today"). Returns null when no rule applies, which future
     * Employee Registration must treat as a hard stop.
     */
    public static function resolveRule(int $branchId, int $employeeTypeId, Carbon $effectiveDate): ?self
    {
        return static::query()
            ->withoutGlobalScopes()
            ->forBranch($branchId)
            ->forEmployeeType($employeeTypeId)
            ->active()
            ->effectiveOn($effectiveDate)
            ->orderByDesc('effective_from')
            ->first();
    }
}
