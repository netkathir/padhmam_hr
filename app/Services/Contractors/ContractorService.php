<?php

namespace App\Services\Contractors;

use App\Models\Contractor;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractorService
{
    private const STATUTORY_KEYS = [
        'pan_number',
        'gstin',
        'pf_registration_number',
        'esi_registration_number',
    ];

    private const CONTACT_KEYS = [
        'contact_person_name',
        'primary_phone',
        'alternate_phone',
        'primary_email',
    ];

    private const LICENCE_KEYS = [
        'labour_licence_number',
        'labour_licence_valid_from',
        'labour_licence_valid_to',
    ];

    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function create(array $data, User $actor, Request $request): Contractor
    {
        $organization = Organization::query()->sole();

        return DB::transaction(function () use ($data, $actor, $organization, $request): Contractor {
            $contractor = Contractor::create([
                ...$data,
                'organization_id' => $organization->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record(
                'contractor_created',
                'contractor',
                $contractor,
                [],
                $this->maskStatutory($contractor->fresh()->toArray()),
                $request,
            );

            return $contractor;
        });
    }

    public function update(Contractor $contractor, array $data, User $actor, Request $request): Contractor
    {
        return DB::transaction(function () use ($contractor, $data, $actor, $request): Contractor {
            $old = $contractor->replicate()->toArray();

            $codeChanged = isset($data['contractor_code']) && $data['contractor_code'] !== $contractor->contractor_code;
            $contactChanged = $this->anyKeyChanged($contractor, $data, self::CONTACT_KEYS);
            $statutoryChanged = $this->anyKeyChanged($contractor, $data, self::STATUTORY_KEYS);
            $licenceChanged = $this->anyKeyChanged($contractor, $data, self::LICENCE_KEYS);

            $contractor->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $contractor->save();

            $new = $contractor->fresh()->toArray();
            $maskedOld = $this->maskStatutory($old);
            $maskedNew = $this->maskStatutory($new);

            $events = array_filter([
                $codeChanged ? 'contractor_code_changed' : null,
                $contactChanged ? 'contractor_contact_details_changed' : null,
                $statutoryChanged ? 'contractor_statutory_details_changed' : null,
                $licenceChanged ? 'contractor_labour_licence_changed' : null,
            ]);

            if ($events === []) {
                $events = ['contractor_updated'];
            }

            foreach ($events as $event) {
                $this->auditService->record($event, 'contractor', $contractor, $maskedOld, $maskedNew, $request);
            }

            return $contractor->fresh();
        });
    }

    public function activate(Contractor $contractor, User $actor, Request $request): Contractor
    {
        return DB::transaction(function () use ($contractor, $actor, $request): Contractor {
            $old = $this->maskStatutory($contractor->replicate()->toArray());

            $contractor->update([
                'status' => 'active',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('contractor_activated', 'contractor', $contractor, $old, $this->maskStatutory($contractor->fresh()->toArray()), $request);

            return $contractor->fresh();
        });
    }

    public function inactivate(Contractor $contractor, User $actor, Request $request): Contractor
    {
        $this->assertCanInactivate($contractor, $request);

        return DB::transaction(function () use ($contractor, $actor, $request): Contractor {
            $old = $this->maskStatutory($contractor->replicate()->toArray());

            $contractor->update([
                'status' => 'inactive',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('contractor_inactivated', 'contractor', $contractor, $old, $this->maskStatutory($contractor->fresh()->toArray()), $request);

            return $contractor->fresh();
        });
    }

    private function assertCanInactivate(Contractor $contractor, Request $request): void
    {
        if ($contractor->hasActiveBranchEngagement()) {
            $this->auditService->record('contractor_inactivation_blocked', 'contractor', $contractor, [], [], $request);

            throw ValidationException::withMessages([
                'status' => 'This Contractor cannot be inactivated because active Branch Engagements are assigned to it. Inactivate the Branch Engagements before continuing.',
            ]);
        }
    }

    private function anyKeyChanged(Contractor $contractor, array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && (string) $data[$key] !== (string) $contractor->getOriginal($key)) {
                return true;
            }
        }

        return false;
    }

    private function maskStatutory(array $values): array
    {
        foreach (self::STATUTORY_KEYS as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = Contractor::maskStatutoryNumber($values[$key]);
            }
        }

        return $values;
    }
}
