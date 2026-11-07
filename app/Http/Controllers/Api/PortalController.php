<?php

namespace App\Http\Controllers\Api;

use App\Models\GameSession;
use App\Models\ChildFeatureSnapshot;
use App\Models\LearningGroup;
use App\Models\Profile;
use App\Models\Response;
use App\Models\Scale;
use App\Models\SupportCase;
use App\Services\LongitudinalProfileService;
use App\Services\SyntheticBenchmarkService;
use App\Services\ProfessionalMeasurementReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PortalController extends BaseController
{
    public function studentProgress(Request $request)
    {
        $profile = $request->user()->assignedProfiles()->wherePivot('role', 'student')->where('type', 'child')->first()
            ?? $request->user()->profiles()->where('type', 'child')->firstOrFail();

        return $this->sendResponse('fetched', [
            'profile_id' => $profile->id,
            'progress' => app(LongitudinalProfileService::class)->build($profile),
        ]);
    }

    public function parentOverview(Request $request)
    {
        $child = $request->user()->assignedProfiles()->wherePivot('role', 'parent')->where('type', 'child')->first()
            ?? $request->user()->profiles()->where('type', 'child')->firstOrFail();
        $summary = $this->profileSummary($child, 'parent');
        $summary['support_invitation'] = $this->parentSupportInvitation($child->supportCase);
        return $this->sendResponse('fetched', $summary);
    }

    public function parentSupportConsent(Request $request)
    {
        $decision = $request->validate(['decision' => ['required', Rule::in(['consent', 'decline'])]])['decision'];
        $child = $request->user()->assignedProfiles()->wherePivot('role', 'parent')->where('type', 'child')->first()
            ?? $request->user()->profiles()->where('type', 'child')->firstOrFail();
        $case = $child->supportCase;
        abort_unless($case && in_array($case->disclosure_status, ['approved', 'shared'], true), 404);
        $case->update($decision === 'consent' ? [
            'consent_status' => 'recorded', 'parent_consent_at' => now(), 'parent_declined_at' => null,
            'status' => $case->status === 'contact_planned' ? 'active' : $case->status,
        ] : [
            'consent_status' => 'declined', 'parent_declined_at' => now(), 'parent_consent_at' => null,
        ]);

        return $this->sendResponse('updated', $this->parentSupportInvitation($case->fresh()));
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
        $profiles = LearningGroup::with(['profiles.user', 'profiles.supportCase.assignedExpert'])->get()->flatMap->profiles
            ->unique('id')->filter(fn ($profile) => $profile->user?->experiment_tag !== 'local_demo')->values();

        return $this->sendResponse('fetched', [
            'viewer_role' => $request->user()->portal_role,
            'summary' => [
                'students' => $profiles->count(),
                'profile_average' => (int) round($profiles->avg(fn ($profile) => $this->profileCompletion($profile->details ?? [])) ?: 0),
                'priority_reviews' => $profiles->filter(fn ($profile) => data_get($profile->details, 'support_review.priority') === 'priority')->count(),
                'context_needed' => $profiles->filter(fn ($profile) => ! in_array(data_get($profile->details, 'support_review.priority'), ['routine', 'follow_up', 'priority'], true))->count(),
                'service_paths' => [
                    'individual_counseling' => $profiles->where('supportCase.service_path', 'individual_counseling')->count(),
                    'group_support' => $profiles->where('supportCase.service_path', 'group_support')->count(),
                    'general_guidance' => $profiles->where('supportCase.service_path', 'general_guidance')->count(),
                    'assessment_pending' => $profiles->filter(fn ($profile) => ! $profile->supportCase || $profile->supportCase->service_path === 'assessment_pending')->count(),
                ],
            ],
            'students' => $profiles->map(fn ($profile) => $this->profileSummary($profile, 'expert'))->values(),
            'synthetic_benchmark' => app(SyntheticBenchmarkService::class)->report(),
        ]);
    }

    public function reviewCase(Request $request, Profile $profile)
    {
        abort_unless($request->user()->user_type === 'admin', 403);
        abort_unless($profile->type === 'child' && $profile->user?->experiment_tag !== 'local_demo' && $profile->learningGroups()->exists(), 404);

        $summary = $this->profileSummary($profile, 'expert');
        $summary['context'] = collect($profile->details ?? [])->only([
            'date_of_birth', 'grade_level', 'school_name', 'curriculum', 'primary_language',
            'strong_subjects', 'support_subjects', 'interests', 'expression_preferences',
            'location_context', 'household_context', 'technology_context', 'accessibility_needs',
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
        $summary['source_evidence'] = $this->sourceEvidence($profile);
        $summary['assessment_report'] = $this->assessmentReport($profile, $summary);
        $summary['longitudinal_profile'] = app(LongitudinalProfileService::class)->build($profile);
        $summary['professional_measurement_report'] = app(ProfessionalMeasurementReportService::class)->build($profile, $summary['longitudinal_profile']);
        $summary['reported_context'] = app(LongitudinalProfileService::class)->reportedContext($profile);

        return $this->sendResponse('fetched', $summary);
    }

    public function updateReviewCase(Request $request, Profile $profile)
    {
        abort_unless($request->user()->user_type === 'admin', 403);
        abort_unless($profile->type === 'child' && $profile->user?->experiment_tag !== 'local_demo' && $profile->learningGroups()->exists(), 404);
        $data = $request->validate([
            'service_path' => ['required', Rule::in(['assessment_pending', 'individual_counseling', 'group_support', 'general_guidance'])],
            'status' => ['required', Rule::in(['new', 'reviewing', 'contact_planned', 'active', 'monitoring', 'closed'])],
            'priority' => ['required', Rule::in(['routine', 'follow_up', 'priority'])],
            'assigned_expert_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('portal_role', 'expert')],
            'rationale' => ['nullable', 'string', 'max:2000'],
            'professional_notes' => ['nullable', 'string', 'max:5000'],
            'consent_status' => ['required', Rule::in(['not_recorded', 'pending', 'recorded', 'declined'])],
            'next_review_at' => ['nullable', 'date'],
            'professional_formulation' => ['nullable', 'array'],
            'professional_formulation.overview' => ['nullable', 'string', 'max:3000'],
            'professional_formulation.strengths' => ['nullable', 'array', 'max:12'],
            'professional_formulation.strengths.*' => ['string', 'max:500'],
            'professional_formulation.current_needs' => ['nullable', 'array', 'max:12'],
            'professional_formulation.current_needs.*' => ['string', 'max:500'],
            'professional_formulation.hypotheses' => ['nullable', 'array', 'max:12'],
            'professional_formulation.hypotheses.*' => ['string', 'max:500'],
            'professional_formulation.limitations' => ['nullable', 'array', 'max:12'],
            'professional_formulation.limitations.*' => ['string', 'max:500'],
            'parent_summary' => ['nullable', 'string', 'max:3000'],
            'disclosure_status' => ['required', Rule::in(['not_prepared', 'draft', 'approved', 'shared'])],
            'disclosure_scope' => ['nullable', 'array'],
            'child_assent_status' => ['required', Rule::in(['not_requested', 'pending', 'assented', 'declined'])],
        ]);
        $case = SupportCase::updateOrCreate(['profile_id' => $profile->id], $data);

        return $this->sendResponse('updated', $this->supportCaseData($case->fresh('assignedExpert')));
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
        $summary = [
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
        if ($audience === 'expert') {
            $summary['support_case'] = $this->supportCaseData($profile->supportCase);
        }

        return $summary;
    }

    private function supportCaseData(?SupportCase $case): array
    {
        if (! $case) return [
            'service_path' => 'assessment_pending', 'status' => 'new', 'priority' => 'routine',
            'assigned_expert_user_id' => null, 'assigned_expert_name' => null, 'rationale' => null,
            'professional_notes' => null, 'consent_status' => 'not_recorded', 'next_review_at' => null,
            'professional_formulation' => null, 'parent_summary' => null, 'disclosure_status' => 'not_prepared',
            'disclosure_scope' => [], 'child_assent_status' => 'not_requested',
        ];

        return [
            'service_path' => $case->service_path,
            'status' => $case->status,
            'priority' => $case->priority,
            'assigned_expert_user_id' => $case->assigned_expert_user_id,
            'assigned_expert_name' => $case->assignedExpert?->name,
            'rationale' => $case->rationale,
            'professional_notes' => $case->professional_notes,
            'consent_status' => $case->consent_status,
            'next_review_at' => optional($case->next_review_at)->format('Y-m-d'),
            'professional_formulation' => $case->professional_formulation,
            'parent_summary' => $case->parent_summary,
            'disclosure_status' => $case->disclosure_status,
            'disclosure_scope' => $case->disclosure_scope ?? [],
            'child_assent_status' => $case->child_assent_status,
            'parent_consent_at' => optional($case->parent_consent_at)->toIso8601String(),
        ];
    }

    private function parentSupportInvitation(?SupportCase $case): ?array
    {
        if (! $case || ! in_array($case->disclosure_status, ['approved', 'shared'], true) || ! $case->parent_summary) return null;

        return [
            'service_path' => $case->service_path,
            'summary' => $case->parent_summary,
            'consent_status' => $case->consent_status,
            'child_assent_status' => $case->child_assent_status,
            'next_review_at' => optional($case->next_review_at)->format('Y-m-d'),
            'can_decide' => in_array($case->consent_status, ['not_recorded', 'pending'], true),
            'disclosure_note' => 'This family summary does not include the restricted psychological formulation, raw answers, or professional notes.',
        ];
    }

    private function assessmentReport(Profile $profile, array $summary): array
    {
        $coverage = $summary['source_coverage'];
        $coveredSources = collect($coverage)->filter(fn ($source) => $source['percent'] >= 50)->count();
        $confidence = $coveredSources === 3 && $summary['evidence_coverage']['play_domains'] >= 3
            ? 'strong_multi_source' : ($coveredSources >= 2 ? 'moderate' : 'limited');
        $missing = collect($coverage)->filter(fn ($source) => $source['percent'] < 50)->keys()->values();

        return [
            'report_type' => 'restricted_professional_working_report',
            'generated_at' => now()->toIso8601String(),
            'evidence_confidence' => $confidence,
            'missing_or_limited_sources' => $missing,
            'formulation' => $profile->supportCase?->professional_formulation,
            'observed_play_domains' => collect($summary['play_signals'])->map(fn ($signal) => [
                'domain' => $signal['domain'], 'observations' => $signal['observations'],
                'features' => $signal['latest_features'],
            ])->values(),
            'interpretation_limits' => [
                'This is a working human-reviewed formulation, not a diagnosis.',
                'Missing sources lower confidence; absence of evidence is not evidence of absence.',
                'Game and questionnaire observations must be interpreted with developmental, language, cultural, educational, and situational context.',
                'A service pathway records a support decision and must not be treated as a clinical label.',
            ],
            'deployment_notice' => 'Research and demonstration use only. Bangladesh legal review is required before production collection or profiling of child behavioral data; consent, access controls, and human review do not by themselves establish legal compliance.',
            'governance_basis' => [
                ['title' => 'WHO AA-HA! adolescent health guidance', 'url' => 'https://www.who.int/initiatives/global-accelerated-action-for-the-health-of-adolescent/aa-ha%21-guidance-2d-edition/chapter-5/section-5-3'],
                ['title' => 'WHO EASE assessment guidance', 'url' => 'https://iris.who.int/bitstream/handle/10665/374996/9789240082755-eng.pdf'],
                ['title' => 'Bangladesh Mental Health Act 2018', 'url' => 'https://bdlaws.minlaw.gov.bd/act-print-1273.html'],
                ['title' => 'Bangladesh Children Act 2013', 'url' => 'https://bdlaws.minlaw.gov.bd/act-details-1119.html'],
                ['title' => 'Bangladesh Personal Data Protection Ordinance 2025 — child data provision', 'url' => 'https://bdlaws.minlaw.gov.bd/act-1574/act-chapter-print-2585.html'],
            ],
        ];
    }

    private function sourceEvidence(Profile $profile): array
    {
        return Response::where('profile_id', $profile->id)
            ->with(['question.scale', 'option'])
            ->get()
            ->filter(fn ($response) => in_array($response->question?->scale?->visible_to, ['child', 'parent', 'teacher'], true))
            ->groupBy(fn ($response) => $response->question->scale->visible_to)
            ->map(fn ($responses) => $responses->map(fn ($response) => [
                'activity' => $response->question->scale->name,
                'construct' => $response->question->construct,
                'prompt' => $response->question->text,
                'answer' => $response->option?->text ?? $response->text_answer ?? $response->numeric_answer,
                'recorded_at' => optional($response->updated_at)->toIso8601String(),
            ])->values())
            ->all();
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
