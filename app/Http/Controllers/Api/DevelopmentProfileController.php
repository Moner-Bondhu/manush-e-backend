<?php

namespace App\Http\Controllers\Api;

use App\Models\LearningGroup;
use App\Models\Profile;
use Illuminate\Http\Request;

class DevelopmentProfileController extends BaseController
{
    public function show(Request $request, Profile $profile)
    {
        $user = $request->user();
        $ownsProfile = $user->profiles()->whereKey($profile->id)->exists();
        $teachesProfile = LearningGroup::where('teacher_user_id', $user->id)
            ->whereHas('profiles', fn ($query) => $query->whereKey($profile->id))
            ->exists();
        abort_unless($ownsProfile || $teachesProfile || $user->user_type === 'admin', 403);

        $features = $profile->featureSnapshots()->latest('measured_at')->get()
            ->groupBy('domain')
            ->map(fn ($items, $domain) => [
                'domain' => $domain,
                'latest' => $items->unique('feature')->values()->map(fn ($item) => [
                    'feature' => $item->feature,
                    'value' => $item->value,
                    'measured_at' => $item->measured_at,
                    'source_type' => $item->source_type,
                    'source_id' => $item->source_id,
                ]),
                'observations' => $items->count(),
            ])->values();

        return $this->sendResponse('fetched', [
            'profile_id' => $profile->id,
            'features' => $features,
        ]);
    }
}
