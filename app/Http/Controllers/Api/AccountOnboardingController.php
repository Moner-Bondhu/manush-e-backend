<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class AccountOnboardingController extends BaseController
{
    public function context(Request $request)
    {
        $user = $request->user()->load(['assignedProfiles', 'profiles']);
        $profiles = $user->profiles->merge($user->assignedProfiles)->unique('id')->values();

        return $this->sendResponse('fetched', [
            'role' => $user->portal_role,
            'is_onboarded' => $user->is_onboarded,
            'profiles' => $profiles->map->only(['id', 'full_name', 'type']),
            'details' => $user->professional_details ?? [],
        ]);
    }

    public function complete(Request $request)
    {
        $data = $request->validate(['data' => ['required', 'array']])['data'];
        $user = $request->user();
        $user->update([
            'professional_details' => array_merge($user->professional_details ?? [], $data),
            'is_onboarded' => true,
        ]);

        return $this->sendResponse('onboarded', ['role' => $user->portal_role]);
    }
}
