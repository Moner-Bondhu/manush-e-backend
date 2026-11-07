<?php

namespace App\Http\Controllers\Api;

use App\Models\GameSession;
use App\Models\ChildFeatureSnapshot;
use App\Models\LearningGroup;
use App\Models\Profile;
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

    public function reviewOverview(Request $request)
    {
        abort_unless($request->user()->user_type === 'admin', 403);
        $profiles = LearningGroup::with('profiles')->get()->flatMap->profiles->unique('id');

        return $this->sendResponse('fetched', [
            'viewer_role' => $request->user()->portal_role,
            'summary' => [
                'students' => $profiles->count(),
                'profile_average' => (int) round($profiles->avg(fn ($profile) => $this->profileCompletion($profile->details ?? [])) ?: 0),
                'priority_reviews' => $profiles->filter(fn ($profile) => data_get($profile->details, 'support_review.priority') === 'priority')->count(),
                'context_needed' => $profiles->filter(fn ($profile) => ! in_array(data_get($profile->details, 'support_review.priority'), ['routine', 'follow_up', 'priority'], true))->count(),
            ],
            'students' => $profiles->map(fn ($profile) => $this->profileSummary($profile, 'expert'))->values(),
        ]);
    }

    public function reviewCase(Request $request, Profile $profile)
    {
        abort_unless($request->user()->user_type === 'admin', 403);
        abort_unless($profile->type === 'child' && $profile->learningGroups()->exists(), 404);

        $summary = $this->profileSummary($profile, 'expert');
        $summary['context'] = collect($profile->details ?? [])->only([
            'date_of_birth', 'grade_level', 'school_name', 'curriculum', 'primary_language',
            'strong_subjects', 'support_subjects', 'interests', 'expression_preferences',
        ]);
        $summary['source_coverage'] = [
            'student' => $this->sourceCoverage($summary['activities']),
            'parent' => $this->sourceCoverage($this->taskFeed($profile, 'parent')),
            'teacher' => $this->sourceCoverage($this->taskFeed($profile, 'teacher')),
        ];
        $summary['source_tasks'] = [
            'parent' => $this->taskFeed($profile, 'parent'),
            'teacher' => $this->taskFeed($profile, 'teacher'),
        ];

        return $this->sendResponse('fetched', $summary);
    }

    private function sourceCoverage($tasks): array
    {
        $total = $tasks->count();
        $complete = $tasks->filter(fn ($task) => ($task['status'] ?? null) === 'complete' || ($task['complete'] ?? false))->count();
        $started = $tasks->filter(fn ($task) => ($task['responses'] ?? 0) > 0)->count();

        return [
            'complete' => $complete,
            'started' => $started,
            'total' => $total,
            'percent' => $total ? (int) round(($complete / $total) * 100) : 0,
        ];
    }

    private function profileSummary($profile, string $audience): array
    {
        $scales = Scale::where('visible_to', 'child')->withCount('questions')->get()->map(function ($scale) use ($profile) {
            $responses = Response::where('profile_id', $profile->id)->whereIn('question_id', $scale->questions()->pluck('id'))->count();
            return ['id' => $scale->id, 'name' => $scale->name, 'complete' => $responses >= $scale->questions_count, 'responses' => $responses, 'total' => $scale->questions_count];
        });
        $completedTasks = $scales->where('complete', true)->count();
        $totalTasks = $scales->count();
        return [
            'id' => $profile->id,
            'name' => $profile->full_name,
            'profile_completion' => $this->profileCompletion($profile->details ?? []),
            'completed_activities' => $scales->where('complete', true)->count(),
            'total_activities' => $scales->count(),
            'task_completion_percent' => $totalTasks ? (int) round(($completedTasks / $totalTasks) * 100) : 0,
            'evidence_coverage' => [
                'profile' => $this->profileCompletion($profile->details ?? []),
                'activities' => $totalTasks ? (int) round(($completedTasks / $totalTasks) * 100) : 0,
                'play_domains' => ChildFeatureSnapshot::where('profile_id', $profile->id)->distinct('domain')->count('domain'),
            ],
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
            'support_review' => $this->supportReview($profile, $audience),
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
                    'Connect one home activity each week to '.($interests[0] ?? 'a current interest').'. / প্রতি সপ্তাহে বাড়ির একটি কাজকে শিশুর বর্তমান আগ্রহের সঙ্গে যুক্ত করুন।',
                    'Offer a choice of ways to share an idea. / ভাব প্রকাশের জন্য কথা বলা, আঁকা বা তৈরি করার মধ্যে পছন্দের সুযোগ দিন।',
                    'Use a short, predictable start-and-finish routine for home learning. / বাড়িতে শেখার জন্য সংক্ষিপ্ত ও পূর্বানুমেয় শুরু-শেষের রুটিন রাখুন।',
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
                'Keep the same learning objective and offer '.($expression[0] ?? 'a visual or verbal').' entry point. / শেখার লক্ষ্য একই রেখে দৃশ্য বা কথার মাধ্যমে শুরু করার সুযোগ দিন।',
                'Connect examples to '.($interests[0] ?? 'the student’s current interests').' before independent practice. / একক অনুশীলনের আগে উদাহরণকে শিক্ষার্থীর আগ্রহের সঙ্গে যুক্ত করুন।',
                'Use a short check for understanding, then adjust task steps rather than lowering the goal. / লক্ষ্য না কমিয়ে সংক্ষিপ্তভাবে বোঝাপড়া যাচাই করে কাজের ধাপ সামঞ্জস্য করুন।',
            ],
            'permission_note' => $audience === 'expert'
                ? 'Expert review combines available evidence and coverage; interpretation remains a human responsibility.'
                : 'Teacher view supports pedagogy and academic planning; expert-only interpretation is not shown.',
        ];
    }

    private function profileCompletion(array $details): int
    {
        $required = ['date_of_birth', 'grade_level', 'school_name', 'curriculum', 'primary_language', 'sleep_hours', 'activity_days', 'interests'];
        $completed = collect($required)->filter(fn ($key) => isset($details[$key]) && $details[$key] !== '' && $details[$key] !== [])->count();

        return (int) round(($completed / count($required)) * 100);
    }

    private function supportReview($profile, string $audience): array
    {
        $details = $profile->details ?? [];
        $review = (array) ($details['support_review'] ?? []);
        $priority = in_array($review['priority'] ?? null, ['routine', 'follow_up', 'priority'], true)
            ? $review['priority']
            : 'context_needed';

        $labels = [
            'routine' => 'Routine support / নিয়মিত সহায়তা',
            'follow_up' => 'Follow up / ফলো-আপ প্রয়োজন',
            'priority' => 'Priority review / অগ্রাধিকার পর্যালোচনা',
            'context_needed' => 'More context needed / আরও তথ্য প্রয়োজন',
        ];

        $actions = array_values((array) ($review[$audience.'_actions'] ?? []));
        if ($actions === []) {
            $actions = $priority === 'context_needed'
                ? ['Complete the student profile and add two classroom or home observations. / শিক্ষার্থীর প্রোফাইল সম্পূর্ণ করুন এবং শ্রেণিকক্ষ বা বাড়ির দুটি পর্যবেক্ষণ যোগ করুন।']
                : ['Continue the current support plan and review new observations regularly. / বর্তমান সহায়তা পরিকল্পনা চালিয়ে যান এবং নতুন পর্যবেক্ষণ নিয়মিত পর্যালোচনা করুন।'];
        }

        return [
            'priority' => $priority,
            'label' => $labels[$priority],
            'summary' => $review['summary'] ?? 'No urgent concern is indicated in the available school-supported information. / স্কুল-সমর্থিত বর্তমান তথ্যে জরুরি উদ্বেগ নির্দেশিত হয়নি।',
            'evidence' => array_values((array) ($review['evidence'] ?? [])),
            'next_actions' => $actions,
            'expert_referral' => in_array($priority, ['follow_up', 'priority'], true) ? [
                'provider' => 'Moner Bondhu',
                'service' => 'Student and family counselling / শিক্ষার্থী ও পরিবার কাউন্সেলিং',
                'url' => 'https://www.monerbondhu.com/services',
                'phone' => '+880 1776632344',
                'note' => 'An authorized adult can contact the provider; this is a referral option, not a diagnosis. / অনুমোদিত প্রাপ্তবয়স্ক সেবাদাতার সঙ্গে যোগাযোগ করতে পারেন; এটি রেফারেলের বিকল্প, রোগনির্ণয় নয়।',
            ] : null,
        ];
    }
}
