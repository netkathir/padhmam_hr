<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationService
{
    private const STATUTORY_KEYS = [
        'pan_number',
        'tan_number',
        'gstin',
        'pf_registration_number',
        'esi_registration_number',
        'professional_tax_number',
    ];

    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function update(Organization $organization, array $data, User $actor, Request $request): Organization
    {
        if (($data['status'] ?? $organization->status) === 'inactive' && $organization->status === 'active') {
            $this->assertCanInactivate($organization);
        }

        return DB::transaction(function () use ($organization, $data, $actor, $request): Organization {
            $old = $organization->replicate()->toArray();

            $organization->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $organization->save();

            $this->auditService->record(
                'organization_update',
                'organization',
                $organization,
                $this->maskStatutory($old),
                $this->maskStatutory($organization->fresh()->toArray()),
                $request,
            );

            return $organization->fresh();
        });
    }

    public function updateLogo(Organization $organization, UploadedFile $file, User $actor, Request $request): Organization
    {
        $previousPath = $organization->logo_path;
        $isReplacement = (bool) $previousPath;

        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('organization-logos', $filename, 'public');

        return DB::transaction(function () use ($organization, $path, $previousPath, $isReplacement, $actor, $request): Organization {
            $organization->update([
                'logo_path' => $path,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record(
                $isReplacement ? 'organization_logo_replaced' : 'organization_logo_added',
                'organization',
                $organization,
                ['logo_path' => $previousPath],
                ['logo_path' => $path],
                $request,
            );

            if ($previousPath) {
                Storage::disk('public')->delete($previousPath);
            }

            return $organization->fresh();
        });
    }

    private function assertCanInactivate(Organization $organization): void
    {
        $hasActiveBranches = Branch::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveBranches) {
            throw ValidationException::withMessages([
                'status' => 'The organization cannot be inactivated while active branches exist. Inactivate all branches first.',
            ]);
        }
    }

    private function maskStatutory(array $values): array
    {
        foreach (self::STATUTORY_KEYS as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = Organization::maskStatutoryNumber($values[$key]);
            }
        }

        return $values;
    }
}
