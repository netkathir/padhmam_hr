<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ContractorDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'contractor_id',
        'contractor_branch_engagement_id',
        'document_type',
        'document_number',
        'issued_date',
        'expiry_date',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'remarks',
        'status',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'expiry_date' => 'date',
            'file_size' => 'integer',
        ];
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(ContractorBranchEngagement::class, 'contractor_branch_engagement_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')->where('expiry_date', '<', now()->toDateString());
    }

    public function scopeExpiringSoon(Builder $query, ?int $days = null): Builder
    {
        $days ??= (int) config('hrms.contractor_licence_expiry_warning_days', 30);
        $today = now()->toDateString();
        $threshold = now()->addDays($days)->toDateString();

        return $query->whereNotNull('expiry_date')->whereBetween('expiry_date', [$today, $threshold]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return (bool) $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(?int $days = null): bool
    {
        if (! $this->expiry_date || $this->isExpired()) {
            return false;
        }

        $days ??= (int) config('hrms.contractor_licence_expiry_warning_days', 30);

        return now()->diffInDays($this->expiry_date, false) <= $days;
    }

    public function typeLabel(): string
    {
        return config("hrms.contractor_document_types.{$this->document_type}", ucfirst((string) $this->document_type));
    }

    public function belongsToBranchId(): ?int
    {
        return $this->contractor_branch_engagement_id ? $this->engagement?->branch_id : null;
    }

    public function deleteStoredFile(): void
    {
        if ($this->file_path) {
            Storage::disk('public')->delete($this->file_path);
        }
    }
}
