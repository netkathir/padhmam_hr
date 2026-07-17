<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeChangeHistory extends Model
{
    protected $fillable = [
        'employee_id',
        'change_type',
        'effective_date',
        'old_values',
        'new_values',
        'reason',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
