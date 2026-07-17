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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use HasFactory, BelongsToBranch;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SEPARATED = 'separated';

    public const SHIFT_TYPE_FIXED = 'fixed';
    public const SHIFT_TYPE_ROTATIONAL = 'rotational';

    public const GENDERS = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];

    public const MARITAL_STATUSES = ['single' => 'Single', 'married' => 'Married', 'other' => 'Other'];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    /**
     * Once an Employee has completed final registration, these fields
     * require a controlled edit path rather than the normal Employee edit
     * form (see spec section 33). Branch and Employee Number are never
     * editable at all through EmployeeService::update().
     */
    public const CRITICAL_FIELDS = [
        'employee_type_id',
        'date_of_joining',
        'contractor_id',
        'contractor_branch_engagement_id',
        'department_id',
        'section_id',
        'designation_id',
        'shift_type',
    ];

    protected $fillable = [
        'employee_uuid',
        'branch_id',
        'employee_type_id',
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'display_name',
        'date_of_birth',
        'gender',
        'marital_status',
        'blood_group',
        'nationality',
        'photo_path',
        'date_of_joining',
        'confirmation_date',
        'probation_applicable',
        'probation_period_days',
        'probation_end_date',
        'department_id',
        'section_id',
        'designation_id',
        'reporting_manager_id',
        'shift_type',
        'fixed_shift_id',
        'shift_type_override_reason',
        'contractor_id',
        'contractor_branch_engagement_id',
        'biometric_identifier',
        'attendance_applicable',
        'leave_applicable',
        'payroll_applicable',
        'overtime_applicable',
        'applicability_override_reason',
        'status',
        'registration_completed_at',
        'activated_at',
        'inactivated_at',
        'separated_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_of_joining' => 'date',
            'confirmation_date' => 'date',
            'probation_end_date' => 'date',
            'probation_applicable' => 'boolean',
            'probation_period_days' => 'integer',
            'attendance_applicable' => 'boolean',
            'leave_applicable' => 'boolean',
            'payroll_applicable' => 'boolean',
            'overtime_applicable' => 'boolean',
            'registration_completed_at' => 'datetime',
            'activated_at' => 'datetime',
            'inactivated_at' => 'datetime',
            'separated_at' => 'datetime',
        ];
    }

    protected function firstName(): Attribute
    {
        return Attribute::make(set: fn (?string $v) => self::normalizeNamePart($v));
    }

    protected function middleName(): Attribute
    {
        return Attribute::make(set: fn (?string $v) => self::normalizeNamePart($v));
    }

    protected function lastName(): Attribute
    {
        return Attribute::make(set: fn (?string $v) => self::normalizeNamePart($v));
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(set: fn (?string $v) => $v !== null ? trim(preg_replace('/\s+/', ' ', $v)) : null);
    }

    protected function biometricIdentifier(): Attribute
    {
        return Attribute::make(
            set: fn (?string $v) => $v !== null && $v !== '' ? trim($v) : null,
        );
    }

    private static function normalizeNamePart(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    public static function buildDisplayName(?string $first, ?string $middle, ?string $last): string
    {
        return collect([$first, $middle, $last])->filter()->implode(' ');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employeeType(): BelongsTo
    {
        return $this->belongsTo(EmployeeType::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_manager_id');
    }

    public function fixedShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'fixed_shift_id');
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class)->orderByDesc('effective_from');
    }

    public function scheduledShiftAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class)->scheduled();
    }

    public function completedShiftAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class)->completed();
    }

    /**
     * Quick-access convenience relation only — the authoritative,
     * date-correct current Shift must always be resolved through
     * EmployeeShiftResolutionService::resolveEmployeeShift(), never by
     * trusting is_current alone (spec section 34).
     */
    public function currentShiftAssignment(): HasOne
    {
        return $this->hasOne(EmployeeShiftAssignment::class)->where('is_current', true)->latest('effective_from');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function contractorBranchEngagement(): BelongsTo
    {
        return $this->belongsTo(ContractorBranchEngagement::class);
    }

    public function contact(): HasOne
    {
        return $this->hasOne(EmployeeContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(EmployeeAddress::class);
    }

    public function currentAddress(): HasOne
    {
        return $this->hasOne(EmployeeAddress::class)->where('address_type', EmployeeAddress::TYPE_CURRENT);
    }

    public function permanentAddress(): HasOne
    {
        return $this->hasOne(EmployeeAddress::class)->where('address_type', EmployeeAddress::TYPE_PERMANENT);
    }

    public function statutoryDetail(): HasOne
    {
        return $this->hasOne(EmployeeStatutoryDetail::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    public function primaryBankAccount(): HasOne
    {
        return $this->hasOne(EmployeeBankAccount::class)->where('is_primary', true);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function changeHistories(): HasMany
    {
        return $this->hasMany(EmployeeChangeHistory::class)->latest();
    }

    public function separation(): HasOne
    {
        return $this->hasOne(EmployeeSeparation::class);
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

    public function scopeSeparated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SEPARATED);
    }

    public function scopeStaff(Builder $query): Builder
    {
        return $query->whereHas('employeeType', fn (Builder $inner) => $inner->where('code', EmployeeType::STAFF));
    }

    public function scopeCompanyLabour(Builder $query): Builder
    {
        return $query->whereHas('employeeType', fn (Builder $inner) => $inner->where('code', EmployeeType::COMPANY_LABOUR));
    }

    public function scopeContractLabour(Builder $query): Builder
    {
        return $query->whereHas('employeeType', fn (Builder $inner) => $inner->where('code', EmployeeType::CONTRACT_LABOUR));
    }

    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeForSection(Builder $query, int $sectionId): Builder
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeForDesignation(Builder $query, int $designationId): Builder
    {
        return $query->where('designation_id', $designationId);
    }

    public function scopeForContractor(Builder $query, int $contractorId): Builder
    {
        return $query->where('contractor_id', $contractorId);
    }

    public function scopeFixedShiftEmployees(Builder $query): Builder
    {
        return $query->where('shift_type', self::SHIFT_TYPE_FIXED);
    }

    public function scopeRotationalShiftEmployees(Builder $query): Builder
    {
        return $query->where('shift_type', self::SHIFT_TYPE_ROTATIONAL);
    }

    public function scopeJoinedBetween(Builder $query, string|Carbon $from, string|Carbon $to): Builder
    {
        $from = $from instanceof Carbon ? $from->toDateString() : $from;
        $to = $to instanceof Carbon ? $to->toDateString() : $to;

        return $query->whereBetween('date_of_joining', [$from, $to]);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_name');
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

    public function isSeparated(): bool
    {
        return $this->status === self::STATUS_SEPARATED;
    }

    public function isStaff(): bool
    {
        return $this->employeeType?->code === EmployeeType::STAFF;
    }

    public function isCompanyLabour(): bool
    {
        return $this->employeeType?->code === EmployeeType::COMPANY_LABOUR;
    }

    public function isContractLabour(): bool
    {
        return $this->employeeType?->code === EmployeeType::CONTRACT_LABOUR;
    }

    public function requiresContractor(): bool
    {
        return $this->employeeType?->requiresContractor() ?? false;
    }

    public function usesFixedShift(): bool
    {
        return $this->shift_type === self::SHIFT_TYPE_FIXED;
    }

    public function usesRotationalShift(): bool
    {
        return $this->shift_type === self::SHIFT_TYPE_ROTATIONAL;
    }

    public function hasCompletedRegistration(): bool
    {
        return $this->registration_completed_at !== null;
    }

    /**
     * Draft, Inactive, and Separated Employees cannot be newly assigned as
     * a Reporting Manager (spec section 18).
     */
    public function canBeReportingManager(): bool
    {
        return $this->isActive();
    }

    public function maskedAadhaar(): ?string
    {
        return $this->statutoryDetail?->maskedAadhaar();
    }

    public function maskedBankAccount(): ?string
    {
        return $this->bankAccounts()->where('is_primary', true)->first()?->maskedAccountNumber();
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
