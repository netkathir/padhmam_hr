<?php

namespace App\Support\Traits;

use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope('branch', function (Builder $builder): void {
            $context = app(BranchContext::class);

            if ($context->isBypassed() || $context->isAllBranchesSelected()) {
                return;
            }

            if ($branchId = $context->currentBranchId()) {
                $builder->where($builder->qualifyColumn('branch_id'), $branchId);
            }
        });

        static::creating(function (Model $model): void {
            self::applyBranchToModel($model);
        });

        static::updating(function (Model $model): void {
            self::applyBranchToModel($model);
        });
    }

    protected static function applyBranchToModel(Model $model): void
    {
        $context = app(BranchContext::class);

        if ($context->isBypassed()) {
            return;
        }

        if ($model->exists && ! $model->isDirty('branch_id')) {
            return;
        }

        $branchId = $context->currentBranchId();

        if (! $branchId) {
            throw ValidationException::withMessages([
                'branch_selection' => 'A specific active branch is required before saving this record.',
            ]);
        }

        $model->branch_id = $branchId;
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->withoutGlobalScope('branch')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
