<?php

namespace Database\Seeders;

use App\Models\ChildFeatureSnapshot;
use App\Models\Demography;
use App\Models\LearningGroup;
use App\Models\Profile;
use App\Models\ProfileUserAssignment;
use App\Models\Response;
use App\Models\Scale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

                if ($student['complete']) {
                    $this->seedEvidence($child, $index);
                }
            }

            $studentLogin = User::updateOrCreate(
                ['phone_number' => '+8801700000021'],
                ['name' => 'Aarav Sen', 'experiment_tag' => 'demo_school', 'user_type' => 'user', 'portal_role' => 'student', 'is_onboarded' => true]
            );
            ProfileUserAssignment::updateOrCreate(
                ['profile_id' => $profileIds[0], 'user_id' => $studentLogin->id, 'role' => 'student'],
                ['relationship' => 'self', 'is_primary' => true]
            );

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
            'interests' => $student['interests'],
        ];
        if (! $student['complete']) {
            $base['support_review'] = $index === 2 ? [
                'priority' => 'priority',
                'summary' => 'Recent teacher and family observations suggest a timely human review would be useful. / সাম্প্রতিক শিক্ষক ও পারিবারিক পর্যবেক্ষণ অনুযায়ী দ্রুত মানবিক পর্যালোচনা উপকারী হতে পারে।',
                'evidence' => ['Reduced classroom participation across two weeks / দুই সপ্তাহ ধরে শ্রেণিকক্ষে অংশগ্রহণ কমেছে', 'Family reports a recent change in sleep routine / পরিবার ঘুমের রুটিনে সাম্প্রতিক পরিবর্তনের কথা জানিয়েছে'],
                'teacher_actions' => ['Arrange a private check-in with the school counsellor. / স্কুল কাউন্সেলরের সঙ্গে ব্যক্তিগত আলোচনার ব্যবস্থা করুন।', 'Keep academic tasks predictable while more context is gathered. / আরও তথ্য সংগ্রহের সময় শিক্ষার কাজগুলো পূর্বানুমেয় রাখুন।'],
                'parent_actions' => ['Share recent changes with the school support contact. / সাম্প্রতিক পরিবর্তন স্কুলের সহায়তা প্রতিনিধিকে জানান।'],
            ] : ['priority' => 'context_needed'];
            return $base;
        }

        return array_merge($base, [
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

    private function seedEvidence(Profile $profile, int $studentIndex): void
    {
        Scale::where('is_published', true)->where('visible_to', 'child')->with('questions.options')->get()->each(function ($scale) use ($profile, $studentIndex) {
            foreach ($scale->questions as $questionIndex => $question) {
                $option = $question->options->get(($questionIndex + $studentIndex) % max(1, $question->options->count()));
                if (! $option) {
                    continue;
                }
                Response::updateOrCreate(
                    ['profile_id' => $profile->id, 'question_id' => $question->id],
                    ['option_id' => $option->id, 'numeric_answer' => $option->value, 'text_answer' => null]
                );
            }
        });

        foreach ([
            ['attention', 'sustained_attention', 0.72], ['working_memory', 'sequence_accuracy', 0.68],
            ['planning', 'planning_efficiency', 0.76], ['social_emotional', 'perspective_taking', 0.81],
        ] as [$domain, $feature, $value]) {
            ChildFeatureSnapshot::updateOrCreate(
                ['profile_id' => $profile->id, 'domain' => $domain, 'feature' => $feature, 'source_type' => 'demo_baseline'],
                ['value' => $value + ($studentIndex * 0.03), 'source_id' => null, 'measured_at' => now()->subDays(7)]
            );
        }
    }
}
