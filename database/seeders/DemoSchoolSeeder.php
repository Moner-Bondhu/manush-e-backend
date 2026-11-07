<?php

namespace Database\Seeders;

use App\Models\ChildFeatureSnapshot;
use App\Models\Demography;
use App\Models\LearningGroup;
use App\Models\Profile;
use App\Models\ProfileUserAssignment;
use App\Models\Response;
use App\Models\Scale;
use App\Models\SupportCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LocalDemoSeeder::class);

        DB::transaction(function () {
            $teacher = User::updateOrCreate(
                ['phone_number' => '+8801700000000'],
                [
                    'name' => 'Ms. Farhana Rahman',
                    'experiment_tag' => 'demo_school',
                    'user_type' => 'teacher',
                    'portal_role' => 'teacher',
                    'is_onboarded' => true,
                    'professional_details' => $this->teacherDetails(),
                ]
            );

            $group = LearningGroup::updateOrCreate(
                ['teacher_user_id' => $teacher->id, 'name' => 'Grade 5 Discovery Lab'],
                ['school_name' => 'Shapla Learning School']
            );

            $students = [
                ['phone' => '+8801700000011', 'student' => 'Aarav Sen', 'parent' => 'Maya Sen', 'gender' => 'male', 'dob' => '2015-03-12', 'complete' => true, 'strengths' => ['Visual reasoning', 'Storytelling', 'Curiosity'], 'interests' => ['Drawing', 'Space', 'Building']],
                ['phone' => '+8801700000012', 'student' => 'Nusrat Jahan', 'parent' => 'Samira Jahan', 'gender' => 'female', 'dob' => '2015-07-24', 'complete' => true, 'strengths' => ['Collaboration', 'Pattern finding', 'Empathy'], 'interests' => ['Football', 'Animals', 'Music']],
                ['phone' => '+8801700000013', 'student' => 'Rafi Islam', 'parent' => 'Tariq Islam', 'gender' => 'male', 'dob' => '2015-01-18', 'complete' => false, 'strengths' => ['Hands-on exploration'], 'interests' => ['Machines', 'Cricket']],
                ['phone' => '+8801700000014', 'student' => 'Anika Das', 'parent' => 'Rina Das', 'gender' => 'female', 'dob' => '2015-10-05', 'complete' => false, 'strengths' => ['Imagination'], 'interests' => ['Dance', 'Comics']],
            ];

            $profileIds = [];
            foreach ($students as $index => $student) {
                $family = User::updateOrCreate(
                    ['phone_number' => $student['phone']],
                    ['name' => $student['parent'], 'experiment_tag' => 'demo_school', 'user_type' => 'user', 'portal_role' => 'parent', 'is_onboarded' => true]
                );
                $child = Profile::updateOrCreate(
                    ['user_id' => $family->id, 'type' => 'child'],
                    ['full_name' => $student['student'], 'relation_type' => 'child', 'details' => $this->childDetails($student, $index)]
                );
                $parent = Profile::updateOrCreate(
                    ['user_id' => $family->id, 'type' => 'parent'],
                    ['full_name' => $student['parent'], 'relation_type' => $index % 2 ? 'mother' : 'guardian', 'details' => $this->parentDetails($student, $index)]
                );
                Demography::updateOrCreate(['profile_id' => $child->id], ['dob' => $student['dob'], 'gender' => $student['gender'], 'grade' => '5']);
                Demography::updateOrCreate(['profile_id' => $parent->id], ['dob' => '1988-01-01', 'gender' => 'other', 'grade' => 'N/A']);
                ProfileUserAssignment::updateOrCreate(
                    ['profile_id' => $child->id, 'user_id' => $family->id, 'role' => 'parent'],
                    ['relationship' => $index % 2 ? 'mother' : 'guardian', 'is_primary' => true]
                );
                $profileIds[] = $child->id;
                $studentLogin = User::updateOrCreate(
                    ['phone_number' => '+88017000000'.(21 + $index)],
                    ['name' => $student['student'], 'experiment_tag' => 'demo_school', 'user_type' => 'user', 'portal_role' => 'student', 'is_onboarded' => true]
                );
                ProfileUserAssignment::updateOrCreate(
                    ['profile_id' => $child->id, 'user_id' => $studentLogin->id, 'role' => 'student'],
                    ['relationship' => 'self', 'is_primary' => true]
                );
                $this->seedEvidence($child, $index, [1, 1, .35, 0][$index]);
                $this->seedAdultEvidence($child, 'parent', [1, 1, .25, 0][$index]);
                $this->seedAdultEvidence($child, 'teacher', [1, 1, .5, 0][$index]);
            }

            User::updateOrCreate(['phone_number' => '+8801700000030'], ['name' => 'School Administrator', 'experiment_tag' => 'demo_school', 'user_type' => 'admin', 'portal_role' => 'admin', 'is_onboarded' => true]);
            $expert = User::updateOrCreate(['phone_number' => '+8801700000031'], [
                'name' => 'Dr. Nabila Karim', 'experiment_tag' => 'demo_school', 'user_type' => 'admin',
                'portal_role' => 'expert', 'is_onboarded' => true, 'professional_details' => $this->expertDetails(),
            ]);

            $casePlans = [
                ['general_guidance', 'monitoring', 'routine', 'Strength-based classroom guidance is appropriate with routine review.'],
                ['group_support', 'contact_planned', 'follow_up', 'A small skills group may support confidence with extended tasks and peer learning.'],
                ['individual_counseling', 'reviewing', 'priority', 'Converging family and teacher observations support a private counselor review.'],
                ['assessment_pending', 'new', 'routine', 'Profile, family, and classroom context should be completed before selecting support.'],
            ];
            foreach (Schema::hasTable('support_cases') ? $profileIds : [] as $index => $profileId) {
                [$servicePath, $status, $priority, $rationale] = $casePlans[$index];
                $caseData = [
                    'assigned_expert_user_id' => $index < 3 ? $expert->id : null,
                    'service_path' => $servicePath, 'status' => $status, 'priority' => $priority,
                    'rationale' => $rationale, 'professional_notes' => $index === 2 ? 'Review recent routine changes privately with the family and school counselor.' : null,
                    'consent_status' => $index < 2 ? 'recorded' : ($index === 2 ? 'pending' : 'not_recorded'),
                    'next_review_at' => now()->addDays([30, 14, 7, 10][$index])->toDateString(),
                ];
                if (Schema::hasColumn('support_cases', 'professional_formulation')) {
                    $caseData = array_merge($caseData, [
                        'professional_formulation' => $this->caseFormulation($index),
                    'parent_summary' => [
                        'Current information supports continuing strengths-based learning and routine observation. / বর্তমান তথ্য শক্তিভিত্তিক শেখা ও নিয়মিত পর্যবেক্ষণ চালিয়ে যাওয়ার পক্ষে।',
                        'A small-group support option may help build confidence and task-planning skills. / ছোট দলে সহায়তা আত্মবিশ্বাস ও কাজ পরিকল্পনার দক্ষতা বাড়াতে সাহায্য করতে পারে।',
                        'The school support team would like to offer a private counselor conversation to understand recent changes and plan support together. / সাম্প্রতিক পরিবর্তন বুঝতে ও একসঙ্গে সহায়তা পরিকল্পনা করতে স্কুল সহায়তা দল একটি ব্যক্তিগত কাউন্সেলর আলোচনার সুযোগ দিতে চায়।',
                        'More information from home, school, and the student is needed before recommending a support option. / সহায়তার বিকল্প প্রস্তাব করার আগে বাড়ি, স্কুল ও শিক্ষার্থীর কাছ থেকে আরও তথ্য প্রয়োজন।',
                    ][$index],
                    'disclosure_status' => $index < 3 ? 'approved' : 'draft',
                    'disclosure_scope' => ['support_path', 'parent_summary', 'consent_request'],
                    'child_assent_status' => $index < 2 ? 'assented' : ($index === 2 ? 'pending' : 'not_requested'),
                    ]);
                }
                SupportCase::updateOrCreate(['profile_id' => $profileId], $caseData);
            }

            $group->profiles()->sync($profileIds);
        });
    }

    private function childDetails(array $student, int $index): array
    {
        $base = [
            'date_of_birth' => $student['dob'],
            'grade_level' => 'Grade 5',
            'student_identifier' => 'SLS-5-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            'school_name' => 'Shapla Learning School',
            'primary_language' => $index % 2 ? 'Bangla and English' : 'English and Bangla',
            'location_context' => ['Dhaka metropolitan school catchment', 'Peri-urban school catchment', 'Recently relocated within Dhaka division', 'Dhaka metropolitan school catchment'][$index],
            'household_context' => ['Two-parent household with shared caregiving', 'Extended-family household', 'Parent-led household with recent routine changes', 'Extended-family caregiving arrangement'][$index],
            'technology_context' => ['Shared family smartphone and school tablet access', 'Personal family smartphone and intermittent broadband', 'Shared Android phone with mobile data', 'Limited shared-device access'][$index],
            'interests' => $student['interests'],
        ];
        if ($index >= 2) {
            $base['support_review'] = $index === 2 ? [
                'priority' => 'priority',
                'summary' => 'Recent teacher and family observations suggest a timely human review would be useful. / সাম্প্রতিক শিক্ষক ও পারিবারিক পর্যবেক্ষণ অনুযায়ী দ্রুত মানবিক পর্যালোচনা উপকারী হতে পারে।',
                'evidence' => ['Reduced classroom participation across two weeks / দুই সপ্তাহ ধরে শ্রেণিকক্ষে অংশগ্রহণ কমেছে', 'Family reports a recent change in sleep routine / পরিবার ঘুমের রুটিনে সাম্প্রতিক পরিবর্তনের কথা জানিয়েছে'],
                'teacher_actions' => ['Arrange a private check-in with the school counsellor. / স্কুল কাউন্সেলরের সঙ্গে ব্যক্তিগত আলোচনার ব্যবস্থা করুন।', 'Keep academic tasks predictable while more context is gathered. / আরও তথ্য সংগ্রহের সময় শিক্ষার কাজগুলো পূর্বানুমেয় রাখুন।'],
                'parent_actions' => ['Share recent changes with the school support contact. / সাম্প্রতিক পরিবর্তন স্কুলের সহায়তা প্রতিনিধিকে জানান।'],
            ] : ['priority' => 'context_needed'];
            if ($index === 2) unset($base['interests']);
            if ($index === 3) $base = array_intersect_key($base, array_flip(['date_of_birth', 'grade_level', 'support_review']));
            return $base;
        }

        $details = array_merge($base, [
            'gender_identity' => $student['gender'],
            'curriculum' => 'National curriculum with English enrichment',
            'strong_subjects' => $index ? ['Science', 'Physical Education'] : ['Art', 'Mathematics'],
            'support_subjects' => $index ? ['Extended writing'] : ['Written composition'],
            'recent_academic_summary' => 'Meeting grade expectations with strong engagement when ideas can be explored visually or collaboratively.',
            'learning_supports' => 'Choice of response format, clear task steps, and short reflection pauses.',
            'school_history' => 'Continuing student with stable attendance and classroom relationships.',
            'attendance_context' => 'Regular attendance.',
            'sleep_hours' => 9,
            'activity_days' => 5,
            'height' => $index ? '142 cm' : '139 cm',
            'weight' => $index ? '37 kg' : '34 kg',
            'nutrition_context' => 'Regular school meals and family-reported balanced diet.',
            'allergies' => 'None reported',
            'medications' => 'None reported',
            'physical_conditions' => 'None reported',
            'vision_hearing_support' => 'No current support reported',
            'health_context' => 'Family reports general good health.',
            'accessibility_needs' => 'No current accommodations requested.',
            'expression_preferences' => $index ? ['Discussion', 'Movement', 'Models'] : ['Drawing', 'Models', 'Short writing'],
            'goals' => 'Build confidence explaining ideas and independently planning multi-step work.',
            'recognized_strengths' => implode(', ', $student['strengths']),
            'creative_activities' => $index ? ['Team sport', 'Music'] : ['Sketching', 'Model making'],
            'career_curiosities' => $index ? ['Veterinary care', 'Sports'] : ['Design', 'Space science'],
            'support_review' => $index ? [
                'priority' => 'follow_up',
                'summary' => 'A short adult follow-up may help connect classroom stamina and current routines. / সংক্ষিপ্ত প্রাপ্তবয়স্ক ফলো-আপ শ্রেণিকক্ষের স্থায়িত্ব ও বর্তমান রুটিনের সম্পর্ক বুঝতে সাহায্য করতে পারে।',
                'evidence' => ['Participation is strong in collaborative work but less consistent during extended writing. / দলীয় কাজে অংশগ্রহণ ভালো, তবে দীর্ঘ লেখার সময় ধারাবাহিকতা কম।'],
                'teacher_actions' => ['Use shorter writing stages and check progress after each stage. / লেখার কাজ ছোট ধাপে ভাগ করুন এবং প্রতিটি ধাপের পর অগ্রগতি দেখুন।'],
                'parent_actions' => ['Keep the evening learning routine short and predictable. / সন্ধ্যার শেখার রুটিন সংক্ষিপ্ত ও পূর্বানুমেয় রাখুন।'],
            ] : [
                'priority' => 'routine',
                'summary' => 'Current information supports continuing routine observation and strengths-based learning. / বর্তমান তথ্য অনুযায়ী নিয়মিত পর্যবেক্ষণ ও শক্তিভিত্তিক শেখা চালিয়ে যাওয়া উপযোগী।',
            ],
        ]);
        return $details;
    }

    private function parentDetails(array $student, int $index): array
    {
        return [
            'education_level' => $index % 2 ? 'Bachelor degree' : 'Higher secondary',
            'occupation' => $index % 2 ? 'Small business owner' : 'Community health worker',
            'preferred_language' => $index % 2 ? 'Bangla' : 'English and Bangla',
            'relationship_to_child' => 'Parent or primary guardian',
            'contact_preference' => 'Phone after 5 PM or school message',
            'learning_devices' => 'Shared Android phone with home mobile internet',
            'study_space' => 'Shared family table',
            'support_availability' => 'An adult is usually available in the evening.',
            'family_strengths' => 'Regular mealtime conversation and weekend learning activities.',
            'current_priorities' => 'Confidence, consistent routines, and enjoying learning.',
            'household_languages' => ['Bangla', 'English'],
            'caregiving_context' => 'Extended family supports after-school routines when needed.',
        ];
    }

    private function teacherDetails(): array
    {
        return [
            'school_name' => 'Shapla Learning School', 'role_title' => 'Grade 5 Lead Teacher',
            'highest_qualification' => 'Master of Education', 'years_experience' => 9,
            'subjects' => ['Mathematics', 'Science', 'Language Arts'], 'grade_levels' => ['Grade 5'],
            'professional_interests' => ['Inclusive pedagogy', 'Project learning', 'Student agency'],
            'curriculum' => 'National curriculum with English enrichment', 'typical_class_size' => 28,
            'languages_used' => ['English', 'Bangla'], 'available_support' => 'School counselor weekly; learning-support teacher twice a week.',
            'development_priorities' => 'Use broader evidence to personalize tasks without lowering learning goals.',
            'assessment_approaches' => 'Work samples, quizzes, observation, student reflection, and projects.',
            'technology_access' => 'Teacher laptop, projector, and shared student tablets.',
        ];
    }

    private function expertDetails(): array
    {
        return [
            'organization' => 'Moner Bondhu', 'role_title' => 'Child and Adolescent Counselor',
            'highest_qualification' => 'Master of Science in Counseling Psychology',
            'years_experience' => 8, 'professional_registration' => 'Demo professional record',
            'specialties' => ['School wellbeing', 'Family support', 'Group facilitation'],
            'languages_used' => ['Bangla', 'English'], 'service_modes' => ['Individual', 'Group', 'School consultation'],
            'availability' => 'Weekdays, school referral hours',
        ];
    }

    private function caseFormulation(int $index): array
    {
        return [
            [
                'overview' => 'Available student, family, teacher, and play evidence is broadly consistent with stable participation and strong visual expression.',
                'strengths' => ['Visual reasoning and imaginative expression', 'Curiosity and persistence with building tasks', 'Stable family and classroom participation'],
                'current_needs' => ['Continue developing written explanation without replacing visual strengths'],
                'hypotheses' => ['Visual entry points may increase engagement with extended written tasks'],
                'limitations' => ['Current evidence reflects one school term and should be reviewed longitudinally'],
            ],
            [
                'overview' => 'Multi-source evidence suggests strong social participation and empathy, with less consistent stamina during extended independent writing.',
                'strengths' => ['Collaboration and perspective-taking', 'Pattern finding', 'Strong engagement in movement and team activities'],
                'current_needs' => ['Task planning and confidence during extended independent work'],
                'hypotheses' => ['A structured peer group may support planning practice and confidence'],
                'limitations' => ['Performance may vary by language and task format'],
            ],
            [
                'overview' => 'Partial student, family, and teacher evidence indicates a recent participation change alongside a reported sleep-routine change. A private review is warranted before interpretation.',
                'strengths' => ['Hands-on exploration', 'Interest in machines and cricket'],
                'current_needs' => ['Clarify recent changes in routine, participation, and support needs'],
                'hypotheses' => ['Sleep or transition context may be affecting classroom participation', 'Additional explanations remain possible'],
                'limitations' => ['Student activities are incomplete', 'Parent evidence is limited', 'No conclusion should be drawn from the current snapshot alone'],
            ],
            [
                'overview' => 'There is not enough multi-source information for a responsible psychological formulation.',
                'strengths' => ['Imagination is reported as a strength'],
                'current_needs' => ['Complete basic profile and gather student, family, and classroom observations'],
                'hypotheses' => [],
                'limitations' => ['Student, parent, teacher, and play evidence are substantially incomplete'],
            ],
        ][$index];
    }

    private function seedEvidence(Profile $profile, int $studentIndex, float $ratio): void
    {
        $questions = Scale::where('is_published', true)->where('visible_to', 'child')->with('questions.options')->get()->flatMap->questions->values();
        $limit = (int) round($questions->count() * $ratio);
        $questions->take($limit)->each(function ($question, $questionIndex) use ($profile, $studentIndex) {
                $option = $question->options->get(($questionIndex + $studentIndex) % max(1, $question->options->count()));
                Response::updateOrCreate(
                    ['profile_id' => $profile->id, 'question_id' => $question->id],
                    ['option_id' => $option?->id, 'numeric_answer' => $option?->value, 'text_answer' => $option ? null : 'A thoughtful generated demo response showing the learner’s approach and ideas.']
                );
        });

        collect([
            ['attention', 'sustained_attention', 0.72], ['working_memory', 'sequence_accuracy', 0.68],
            ['planning', 'planning_efficiency', 0.76], ['social_emotional', 'perspective_taking', 0.81],
        ])->take((int) round(4 * $ratio))->each(function ($item) use ($profile, $studentIndex) {
            [$domain, $feature, $value] = $item;
            ChildFeatureSnapshot::updateOrCreate(
                ['profile_id' => $profile->id, 'domain' => $domain, 'feature' => $feature, 'source_type' => 'demo_baseline'],
                ['value' => $value + ($studentIndex * 0.03), 'source_id' => null, 'measured_at' => now()->subDays(7)]
            );
        });
    }

    private function seedAdultEvidence(Profile $profile, string $audience, float $ratio): void
    {
        $questions = Scale::where('is_published', true)->where('visible_to', $audience)->with('questions.options')->get()->flatMap->questions->values();
        $questions->take((int) round($questions->count() * $ratio))->each(function ($question) use ($profile) {
            $option = $question->options->first();
            Response::updateOrCreate(['profile_id' => $profile->id, 'question_id' => $question->id], [
                'option_id' => $option?->id, 'numeric_answer' => $option?->value, 'text_answer' => $option ? null : 'Generated school-supported observation for demonstration.',
            ]);
        });
    }
}
