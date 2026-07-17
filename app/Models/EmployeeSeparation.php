<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSeparation extends Model
{
    public const TYPE_RESIGNATION = 'resignation';
    public const TYPE_TERMINATION = 'termination';
    public const TYPE_RETIREMENT = 'retirement';
    public const TYPE_CONTRACT_COMPLETION = 'contract_completion';
    public const TYPE_DEATH = 'death';
    public const TYPE_ABANDONMENT = 'abandonment';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_RESIGNATION => 'Resignation',
        self::TYPE_TERMINATION => 'Termination',
        self::TYPE_RETIREMENT => 'Retirement',
        self::TYPE_CONTRACT_COMPLETION => 'Contract Completion',
        self::TYPE_DEATH => 'Death',
        self::TYPE_ABANDONMENT => 'Abandonment',
        self::TYPE_OTHER => 'Other',
    ];

    protected $fillable = [
        'employee_id',
        'separation_type',
        'last_working_date',
        'separation_reason',
        'notice_date',
        'remarks',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'last_working_date' => 'date',
            'notice_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->separation_type] ?? ucfirst($this->separation_type);
    }
}
