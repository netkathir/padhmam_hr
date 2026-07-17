<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AuditService
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'remember_token',
        'token',
        'reset_token',
        'session_id',
        'api_token',
        'secret',
    ];

    public function __construct(private readonly BranchContext $branchContext)
    {
    }

    public function record(
        string $event,
        string $module,
        array|Model|null $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?Request $request = null,
    ): AuditLog {
        $audit = new AuditLog();
        $audit->branch_id = $this->branchContext->currentBranchId();
        $audit->user_id = $this->branchContext->currentUser()?->id;
        $audit->event = $event;
        $audit->module = $module;
        $audit->auditable_type = $auditable instanceof Model ? $auditable::class : data_get($auditable, 'auditable_type');
        $audit->auditable_id = $auditable instanceof Model ? $auditable->getKey() : data_get($auditable, 'auditable_id');
        $audit->old_values = $this->sanitize($oldValues);
        $audit->new_values = $this->sanitize($newValues);
        $audit->ip_address = $request?->ip();
        $audit->user_agent = $request?->userAgent();
        $audit->route = $request?->route()?->getName();
        $audit->request_method = $request?->method();
        $audit->save();

        return $audit;
    }

    public function snapshot(Model $model): array
    {
        return $this->sanitize($model->getAttributes());
    }

    public function sanitize(array $values): array
    {
        return Arr::except($values, self::SENSITIVE_KEYS);
    }
}
