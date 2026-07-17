<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\OrganizationLogoUpdateRequest;
use App\Http\Requests\Administration\OrganizationUpdateRequest;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationService $organizationService)
    {
    }

    public function show(): View
    {
        $organization = Organization::query()->sole();

        $this->authorize('view', $organization);

        return view('administration.organization.show', [
            'organization' => $organization,
            'canEdit' => auth()->user()?->can('update', $organization) ?? false,
        ]);
    }

    public function edit(): View
    {
        $organization = Organization::query()->sole();

        $this->authorize('update', $organization);

        return view('administration.organization.edit', compact('organization'));
    }

    public function update(OrganizationUpdateRequest $request): RedirectResponse
    {
        $organization = Organization::query()->sole();

        $this->organizationService->update($organization, $request->validated(), $request->user(), $request);

        return redirect()->route('organization.show')->with('status', 'Organization profile updated successfully.');
    }

    public function updateLogo(OrganizationLogoUpdateRequest $request): RedirectResponse
    {
        $organization = Organization::query()->sole();

        $this->organizationService->updateLogo($organization, $request->file('logo'), $request->user(), $request);

        return redirect()->route('organization.show')->with('status', 'Organization logo updated successfully.');
    }
}
