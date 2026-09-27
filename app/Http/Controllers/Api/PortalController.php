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
        $child = $request->user()->assignedProfiles()->wherePivot('role', 'parent')->where('type', 'child')->first()
            ?? $request->user()->profiles()->where('type', 'child')->firstOrFail();
        return $this->sendResponse('fetched', $this->profileSummary($child, 'parent'));
    }

    public function teacherOverview(Request $request)
    {
        abort_unless(in_array($request->user()->user_type, ['teacher', 'admin'], true), 403);
        $groups = LearningGroup::where('teacher_user_id', $request->user()->id)->with('profiles')->get();
        return $this->sendResponse('fetched', $groups->map(fn ($group) => [
            'id' => $group->id,
            'name' => $group->name,
            'school_name' => $group->school_name,
            'students' => $group->profiles->map(fn ($profile) => $this->profileSummary($profile, 'teacher')),
        ]));
    }

    private function profileSummary($profile, string $audience): array
    {
        $scales = Scale::where('visible_to', 'child')->withCount('questions')->get()->map(function ($scale) use ($profile) {
            $responses = Response::where('profile_id', $profile->id)->whereIn('question_id', $scale->questions()->pluck('id'))->count();
            return ['id' => $scale->id, 'name' => $scale->name, 'complete' => $responses >= $scale->questions_count, 'responses' => $responses, 'total' => $scale->questions_count];
        });
        return [
            'id' => $profile->id,
            'name' => $profile->full_name,
            'profile_completion' => $this->profileCompletion($profile->details ?? []),
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
            'assigned_tasks' => $this->taskFeed($profile, $audience),
            'personalization' => $this->personalization($profile, $audience),
        ];
    }

    private function taskFeed($profile, string $audience)
    {
        return Scale::where('visible_to', $audience)->where('is_published', true)->whereNotNull('code')
            ->withCount('questions')->orderBy('name')->get()->map(function ($scale) use ($profile) {
                $responses = Response::where('profile_id', $profile->id)
                    ->whereIn('question_id', $scale->questions()->pluck('id'))->count();
                return [
                    'id' => $scale->id,
                    'name' => $scale->name,
                    'description' => $scale->description,
                    'responses' => $responses,
                    'total' => $scale->questions_count,
                    'status' => $responses >= $scale->questions_count ? 'complete' : ($responses > 0 ? 'in_progress' : 'ready'),
                ];
            })->values();
    }

    private function personalization($profile, string $audience): array
    {
        $details = $profile->details ?? [];
        $interests = array_values((array) ($details['interests'] ?? []));
        $expression = array_values((array) ($details['expression_preferences'] ?? []));

        if ($audience === 'parent') {
            return [
                'interests' => $interests,
                'support_ideas' => [
                    'Connect one home activity each week to '.($interests[0] ?? 'a current interest').'.',
                    'Offer a choice between '.($expression[0] ?? 'talking').' and '.($expression[1] ?? 'drawing or making').' when sharing an idea.',
                    'Use a short, predictable start-and-finish routine for home learning.',
                ],
                'permission_note' => 'Family view shows practical supports and participation, not clinical interpretation.',
            ];
        }

        return [
            'academic_strengths' => array_values((array) ($details['strong_subjects'] ?? [])),
            'support_subjects' => array_values((array) ($details['support_subjects'] ?? [])),
            'interests' => $interests,
            'expression_preferences' => $expression,
            'teaching_ideas' => [
                'Keep the same learning objective and offer '.($expression[0] ?? 'a visual or verbal').' entry point.',
                'Connect examples to '.($interests[0] ?? 'the student’s current interests').' before independent practice.',
                'Use a short check for understanding, then adjust task steps rather than lowering the goal.',
            ],
            'permission_note' => 'Teacher view supports pedagogy and academic planning; expert-only interpretation is not shown.',
        ];
    }

    private function profileCompletion(array $details): int
    {
        $required = ['date_of_birth', 'grade_level', 'school_name', 'curriculum', 'primary_language', 'sleep_hours', 'activity_days', 'interests'];
        $completed = collect($required)->filter(fn ($key) => isset($details[$key]) && $details[$key] !== '' && $details[$key] !== [])->count();

        return (int) round(($completed / count($required)) * 100);
    }
}
