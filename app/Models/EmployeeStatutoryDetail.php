<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeStatutoryDetail extends Model
{
    protected $fillable = [
        'employee_id',
        'aadhaar_number',
        'pan_number',
        'uan_number',
        'pf_number',
        'esi_number',
        'professional_tax_applicable',
        'pf_applicable',
        'esi_applicable',
        'tds_applicable',
    ];

    protected $hidden = [
        'aadhaar_number',
    ];

    protected function casts(): array
    {
        return [
            // Application-layer encryption using APP_KEY (see spec section 26)
            // — Aadhaar is never stored or logged in plain text.
            'aadhaar_number' => 'encrypted',
            'professional_tax_applicable' => 'boolean',
            'pf_applicable' => 'boolean',
            'esi_applicable' => 'boolean',
            'tds_applicable' => 'boolean',
        ];
    }

    protected function aadhaarNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? preg_replace('/\s+/', '', trim($value)) : null,
        );
    }

    protected function panNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    protected function uanNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? trim($value) : null,
        );
    }

    protected function pfNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? trim($value) : null,
        );
    }

    protected function esiNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null && $value !== '' ? trim($value) : null,
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function maskedAadhaar(): ?string
    {
        return self::maskLastFour($this->aadhaar_number);
    }

    public function maskedPan(): ?string
    {
        return self::maskLastFour($this->pan_number);
    }

    public static function maskLastFour(?string $value): ?string
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
