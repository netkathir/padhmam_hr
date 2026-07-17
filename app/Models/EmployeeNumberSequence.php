<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeNumberSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_number_rule_id',
        'sequence_period',
        'last_issued_number',
        'next_number',
        'last_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_issued_number' => 'integer',
            'next_number' => 'integer',
            'last_generated_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(EmployeeNumberRule::class, 'employee_number_rule_id');
    }

    public function hasIssuedNumbers(): bool
    {
        return $this->last_issued_number > 0;
    }
}
