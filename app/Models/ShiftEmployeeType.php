<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ShiftEmployeeType extends Pivot
{
    protected $table = 'shift_employee_types';

    public $incrementing = true;

    protected $fillable = [
        'shift_id',
        'employee_type_id',
    ];
}
