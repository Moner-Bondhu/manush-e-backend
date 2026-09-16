<?php

namespace App\Http\Controllers\Api;

use App\Models\GameSession;
use App\Models\ChildFeatureSnapshot;
use App\Models\LearningGroup;
use App\Models\Response;
use App\Models\Scale;
use Illuminate\Http\Request;

class PortalController extends BaseController
{
    public function parentOverview(Request $request)
    {
        $child = $request->user()->profiles()->where('type', 'child')->firstOrFail();
        return $this->sendResponse('fetched', $this->profileSummary($child));
    }

    public function teacherOverview(Request $request)
    {
        abort_unless(in_array($request->user()->user_type, ['teacher', 'admin'], true), 403);
        $groups = LearningGroup::where('teacher_user_id', $request->user()->id)->with('profiles')->get();
        return $this->sendResponse('fetched', $groups->map(fn ($group) => [
            'id' => $group->id,
            'name' => $group->name,
            'school_name' => $group->school_name,
            'students' => $group->profiles->map(fn ($profile) => $this->profileSummary($profile)),
        ]));
    }

    private function profileSummary($profile): array
    {
        $scales = Scale::where('visible_to', 'child')->withCount('questions')->get()->map(function ($scale) use ($profile) {
            $responses = Response::where('profile_id', $profile->id)->whereIn('question_id', $scale->questions()->pluck('id'))->count();
            return ['id' => $scale->id, 'name' => $scale->name, 'complete' => $responses >= $scale->questions_count, 'responses' => $responses, 'total' => $scale->questions_count];
        });
        return [
            'id' => $profile->id,
            'name' => $profile->full_name,
            'completed_activities' => $scales->where('complete', true)->count(),
            'total_activities' => $scales->count(),
            'recent_game_sessions' => GameSession::where('profile_id', $profile->id)->where('status', 'completed')->latest('completed_at')->take(3)->get(['id', 'game_definition_id', 'duration_ms', 'completed_at']),
            'play_signals' => ChildFeatureSnapshot::where('profile_id', $profile->id)->latest('measured_at')->get()
                ->groupBy('domain')->map(fn ($items, $domain) => [
                    'domain' => $domain,
                    'observations' => $items->count(),
                    'latest_features' => $items->unique('feature')->take(2)->map(fn ($item) => [
                        'feature' => $item->feature,
                        'value' => $item->value,
                    ])->values(),
                ])->values(),
            'activities' => $scales->values(),
        ];
    }
}
