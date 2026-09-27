<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ScaleResource;
use App\Models\Profile;
use App\Models\Response;
use App\Models\Scale;
use App\Models\LearningGroup;
use Illuminate\Http\Request;

class ScaleController extends BaseController
{
    public function library(Request $request)
    {
        $audience = $request->query('audience');
        $scales = Scale::query()
            ->where('is_published', true)
            ->when($audience, fn ($query) => $query->where('visible_to', $audience))
            ->withCount('questions')
            ->orderBy('domain')
            ->orderBy('name')
            ->get();

        return $this->sendResponse('fetched', [
            'summary' => [
                'packs' => $scales->count(),
                'questions' => $scales->sum('questions_count'),
                'domains' => $scales->pluck('domain')->unique()->values()->count(),
                'audiences' => $scales->pluck('visible_to')->unique()->values(),
            ],
            'domains' => $scales->groupBy('domain')->map(fn ($items, $domain) => [
                'domain' => $domain,
                'packs' => $items->values(),
            ])->values(),
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $profile)
    {
        try {
            // Fetch all scales visible to the profile
            $scales = Scale::where('visible_to', $profile)
                ->where('is_published', true)
                ->whereNotNull('code')
                ->get();
            $user = $request->user();
            $requestedProfileId = $request->query('profile_id');
            $selectedProfile = $requestedProfileId !== null
                ? Profile::findOrFail((int) $requestedProfileId)
                : ($user->profiles()->where('type', $profile)->first()
                    ?? $user->assignedProfiles()->where('type', $profile)->firstOrFail());
            $this->authorizeProfile($request, $selectedProfile);
            $profile_id = $selectedProfile->id;

            // Transform the scales collection to include custom data
            $scales = $scales->map(function ($scale) use ($profile_id) {
                // Count total questions in the scale
                $totalQuestions = $scale->questions()->count();

                // Count responses for the profile related to this scale
                $responseCount = Response::where('profile_id', $profile_id)
                ->join('questions', 'responses.question_id', '=', 'questions.id')
                ->where('questions.scale_id', $scale->id)
                ->count();

                // Determine status
                $status = $responseCount < $totalQuestions ? 'incomplete' : 'complete';

                // Return the scale data with custom fields
                return array_merge(
                    $scale->toArray(),
                    [
                        'totalQuestions' => $totalQuestions,
                        'response_count' => $responseCount,
                        'status' => $status,
                    ]
                );
            });

            return $this->sendResponse('fetched', $scales);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), $th->getLine(), 500);
        }
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $scale_id)
    {
        try {
            if ($request->query('profile_id') !== null) {
                $this->authorizeProfile($request, Profile::findOrFail((int) $request->query('profile_id')));
            }
            $scale = new ScaleResource(Scale::find($scale_id)->load('questions.options'));
            return $this->sendResponse('fetched', $scale);
        } catch (\Throwable $th) {
            //throw $th;
            return $this->sendError($th->getMessage(), $th->getLine(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Scale $scale)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Scale $scale)
    {
        //
    }

    private function authorizeProfile(Request $request, Profile $profile): void
    {
        $user = $request->user();
        $owns = $user->profiles()->whereKey($profile->id)->exists();
        $assigned = $user->assignedProfiles()->whereKey($profile->id)->exists();
        $teaches = LearningGroup::where('teacher_user_id', $user->id)
            ->whereHas('profiles', fn ($query) => $query->whereKey($profile->id))->exists();
        abort_unless($owns || $assigned || $teaches || $user->user_type === 'admin', 403);
    }
}
