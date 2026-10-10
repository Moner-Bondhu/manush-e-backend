<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
class ProfileSetupController extends BaseController
{
    public function show(Request $request, string $role)
    {
        abort_unless(in_array($role, ['child', 'parent', 'teacher'], true), 404);
        [$record, $data] = $this->recordAndData($request, $role);

        return $this->sendResponse('fetched', [
            'role' => $role,
            'sections' => $this->schema()[$role],
            'data' => $data,
            'completion' => $this->completion($role, $data),
            'subject_name' => $role === 'teacher' ? $request->user()->name : $record->full_name,
        ]);
    }

    public function update(Request $request, string $role)
    {
        abort_unless(in_array($role, ['child', 'parent', 'teacher'], true), 404);
        $allowed = collect($this->schema()[$role])->pluck('fields')->flatten(1)->pluck('key')->all();
        $payload = $request->validate([
            'data' => ['required', 'array'],
            'data.*' => ['nullable'],
        ])['data'];
        $payload = collect($payload)->only($allowed)->map(function ($value) {
            if (is_string($value)) return trim($value);
            return $value;
        })->all();

        [$record, $existing] = $this->recordAndData($request, $role);
        $merged = array_merge($existing, $payload);
        if ($role === 'teacher') {
            $record->update(['professional_details' => $merged]);
        } else {
            $record->update(['details' => $merged]);
        }

        return $this->sendResponse('updated', [
            'data' => $merged,
            'completion' => $this->completion($role, $merged),
        ]);
    }

    private function recordAndData(Request $request, string $role): array
    {
        if ($role === 'teacher') {
            abort_unless(in_array($request->user()->user_type, ['teacher', 'admin'], true), 403);
            return [$request->user(), $request->user()->professional_details ?? []];
        }
        $profile = $request->user()->profiles()->where('type', $role)->first();
        if (!$profile) {
            $assignmentRoles = $role === 'child' ? ['student', 'parent'] : [$role];
            $assignment = $request->user()->profileAssignments()
                ->whereIn('role', $assignmentRoles)
                ->whereHas('profile', fn ($query) => $query->where('type', $role))
                ->with('profile.demography')
                ->first();
            $profile = $assignment?->profile;
        }
        abort_unless($profile, 404);

        $data = $profile->details ?? [];
        if ($role === 'child' && $profile->demography) {
            $data = array_merge([
                'date_of_birth' => optional($profile->demography->dob)->format('Y-m-d'),
                'grade_level' => $profile->demography->grade,
            ], $data);
        }
        return [$profile, $data];
    }

    private function completion(string $role, array $data): array
    {
        $required = collect($this->schema()[$role])->pluck('fields')->flatten(1)->where('required', true)->pluck('key');
        $completed = $required->filter(fn ($key) => array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '' && $data[$key] !== [])->count();
        $total = $required->count();
        return ['percent' => $total ? (int) round(($completed / $total) * 100) : 100, 'completed' => $completed, 'required' => $total];
    }

    private function schema(): array
    {
        return [
            'child' => [
                ['key' => 'identity', 'title' => 'Student details / শিক্ষার্থীর তথ্য', 'fields' => [
                    ['key' => 'date_of_birth', 'label' => 'Date of birth / জন্মতারিখ', 'type' => 'date', 'required' => true],
                    ['key' => 'grade_level', 'label' => 'Current grade or class / বর্তমান শ্রেণি', 'type' => 'text', 'required' => true],
                    ['key' => 'student_identifier', 'label' => 'School student ID / স্কুল শিক্ষার্থী আইডি', 'type' => 'text', 'required' => false],
                    ['key' => 'gender_identity', 'label' => 'Gender, if relevant / প্রাসঙ্গিক হলে লিঙ্গ', 'type' => 'text', 'required' => false],
                ]],
                ['key' => 'academic', 'title' => 'Academic profile / শিক্ষাগত প্রোফাইল', 'fields' => [
                    ['key' => 'school_name', 'label' => 'School name / স্কুলের নাম', 'type' => 'text', 'required' => true],
                    ['key' => 'curriculum', 'label' => 'Curriculum or board / পাঠ্যক্রম বা বোর্ড', 'type' => 'text', 'required' => true],
                    ['key' => 'primary_language', 'label' => 'Primary learning language / শেখার প্রধান ভাষা', 'type' => 'text', 'required' => true],
                    ['key' => 'strong_subjects', 'label' => 'Subjects that currently feel strong / বর্তমানে ভালো লাগে এমন বিষয়', 'type' => 'tags', 'required' => false],
                    ['key' => 'support_subjects', 'label' => 'Subjects where support may help / যে বিষয়ে সহায়তা উপকারী হতে পারে', 'type' => 'tags', 'required' => false],
                    ['key' => 'recent_academic_summary', 'label' => 'Recent grades or academic summary / সাম্প্রতিক ফলাফল বা শিক্ষাগত সারাংশ', 'type' => 'textarea', 'required' => false],
                    ['key' => 'learning_supports', 'label' => 'Current learning supports or accommodations / বর্তমান শেখার সহায়তা বা বিশেষ ব্যবস্থা', 'type' => 'textarea', 'required' => false],
                    ['key' => 'school_history', 'label' => 'Relevant school or learning history / প্রাসঙ্গিক স্কুল বা শেখার ইতিহাস', 'type' => 'textarea', 'required' => false],
                    ['key' => 'attendance_context', 'label' => 'Attendance or school-change context / উপস্থিতি বা স্কুল বদলের প্রেক্ষাপট', 'type' => 'textarea', 'required' => false],
                ]],
                ['key' => 'physical', 'title' => 'Physical wellbeing / শারীরিক সুস্থতা', 'fields' => [
                    ['key' => 'sleep_hours', 'label' => 'Typical sleep hours / সাধারণত ঘুমের সময়', 'type' => 'number', 'required' => true],
                    ['key' => 'activity_days', 'label' => 'Active days each week / সপ্তাহে সক্রিয় দিনের সংখ্যা', 'type' => 'number', 'required' => true],
                    ['key' => 'height', 'label' => 'Height, if tracked / উচ্চতা, জানা থাকলে', 'type' => 'text', 'required' => false],
                    ['key' => 'weight', 'label' => 'Weight, if tracked / ওজন, জানা থাকলে', 'type' => 'text', 'required' => false],
                    ['key' => 'nutrition_context', 'label' => 'Nutrition or meal context / পুষ্টি বা খাবারের প্রেক্ষাপট', 'type' => 'textarea', 'required' => false],
                    ['key' => 'allergies', 'label' => 'Allergies / অ্যালার্জি', 'type' => 'textarea', 'required' => false],
                    ['key' => 'medications', 'label' => 'Medications shared with the school / স্কুলকে জানানো ওষুধ', 'type' => 'textarea', 'required' => false],
                    ['key' => 'physical_conditions', 'label' => 'Relevant physical conditions or injuries / প্রাসঙ্গিক শারীরিক অবস্থা বা আঘাত', 'type' => 'textarea', 'required' => false],
                    ['key' => 'vision_hearing_support', 'label' => 'Vision or hearing support / দৃষ্টি বা শ্রবণ সহায়তা', 'type' => 'textarea', 'required' => false],
                    ['key' => 'health_context', 'label' => 'Relevant health context shared by family / পরিবারের জানানো প্রাসঙ্গিক স্বাস্থ্য তথ্য', 'type' => 'textarea', 'required' => false],
                    ['key' => 'accessibility_needs', 'label' => 'Accessibility or accommodation needs / প্রবেশগম্যতা বা বিশেষ সহায়তার প্রয়োজন', 'type' => 'textarea', 'required' => false],
                ]],
                ['key' => 'potential', 'title' => 'Interests and potential / আগ্রহ ও সম্ভাবনা', 'fields' => [
                    ['key' => 'interests', 'label' => 'Interests and favourite activities / আগ্রহ ও পছন্দের কাজ', 'type' => 'tags', 'required' => true],
                    ['key' => 'expression_preferences', 'label' => 'Preferred ways to express ideas / ভাব প্রকাশের পছন্দের উপায়', 'type' => 'tags', 'required' => false],
                    ['key' => 'goals', 'label' => 'Skills the student wants to develop / শিক্ষার্থী যে দক্ষতা বাড়াতে চায়', 'type' => 'textarea', 'required' => false],
                    ['key' => 'recognized_strengths', 'label' => 'Strengths noticed by adults / বড়দের লক্ষ্য করা শক্তি', 'type' => 'textarea', 'required' => false],
                    ['key' => 'creative_activities', 'label' => 'Creative, cultural, or community activities / সৃজনশীল, সাংস্কৃতিক বা সামাজিক কাজ', 'type' => 'tags', 'required' => false],
                    ['key' => 'career_curiosities', 'label' => 'Career or future curiosities / পেশা বা ভবিষ্যৎ নিয়ে আগ্রহ', 'type' => 'tags', 'required' => false],
                ]],
            ],
            'parent' => [
                ['key' => 'background', 'title' => 'Parent or guardian background / অভিভাবকের পরিচিতি', 'fields' => [
                    ['key' => 'education_level', 'label' => 'Highest educational qualification / সর্বোচ্চ শিক্ষাগত যোগ্যতা', 'type' => 'text', 'required' => true],
                    ['key' => 'occupation', 'label' => 'Occupation / পেশা', 'type' => 'text', 'required' => true],
                    ['key' => 'preferred_language', 'label' => 'Preferred communication language / যোগাযোগের পছন্দের ভাষা', 'type' => 'text', 'required' => true],
                    ['key' => 'relationship_to_child', 'label' => 'Relationship to child / শিশুর সঙ্গে সম্পর্ক', 'type' => 'text', 'required' => true],
                    ['key' => 'contact_preference', 'label' => 'Preferred contact method and time / যোগাযোগের পছন্দের মাধ্যম ও সময়', 'type' => 'text', 'required' => false],
                ]],
                ['key' => 'home', 'title' => 'Home learning context / বাড়িতে শেখার প্রেক্ষাপট', 'fields' => [
                    ['key' => 'learning_devices', 'label' => 'Learning devices and internet access / শেখার ডিভাইস ও ইন্টারনেট সুবিধা', 'type' => 'textarea', 'required' => true],
                    ['key' => 'study_space', 'label' => 'Study-space availability / পড়ার জায়গার সুবিধা', 'type' => 'text', 'required' => false],
                    ['key' => 'support_availability', 'label' => 'Who can support learning at home / বাড়িতে শেখায় কে সহায়তা করতে পারেন', 'type' => 'textarea', 'required' => false],
                    ['key' => 'family_strengths', 'label' => 'Family strengths and routines / পরিবারের শক্তি ও রুটিন', 'type' => 'textarea', 'required' => false],
                    ['key' => 'current_priorities', 'label' => 'Current priorities or concerns / বর্তমান অগ্রাধিকার বা উদ্বেগ', 'type' => 'textarea', 'required' => false],
                    ['key' => 'household_languages', 'label' => 'Languages used at home / বাড়িতে ব্যবহৃত ভাষা', 'type' => 'tags', 'required' => false],
                    ['key' => 'caregiving_context', 'label' => 'Caregiving or household context relevant to learning / শেখার সঙ্গে প্রাসঙ্গিক পরিচর্যা বা পারিবারিক প্রেক্ষাপট', 'type' => 'textarea', 'required' => false],
                ]],
            ],
            'teacher' => [
                ['key' => 'professional', 'title' => 'Professional profile / পেশাগত প্রোফাইল', 'fields' => [
                    ['key' => 'school_name', 'label' => 'School name / স্কুলের নাম', 'type' => 'text', 'required' => true],
                    ['key' => 'role_title', 'label' => 'Role or title / ভূমিকা বা পদবি', 'type' => 'text', 'required' => true],
                    ['key' => 'highest_qualification', 'label' => 'Highest qualification / সর্বোচ্চ যোগ্যতা', 'type' => 'text', 'required' => true],
                    ['key' => 'years_experience', 'label' => 'Years of experience / অভিজ্ঞতার বছর', 'type' => 'number', 'required' => true],
                    ['key' => 'subjects', 'label' => 'Subjects taught / পড়ানো বিষয়', 'type' => 'tags', 'required' => true],
                    ['key' => 'grade_levels', 'label' => 'Grade levels / শ্রেণি', 'type' => 'tags', 'required' => true],
                    ['key' => 'professional_interests', 'label' => 'Teaching strengths and professional interests / শিক্ষাদানের শক্তি ও পেশাগত আগ্রহ', 'type' => 'tags', 'required' => false],
                ]],
                ['key' => 'classroom', 'title' => 'Classroom context / শ্রেণিকক্ষের প্রেক্ষাপট', 'fields' => [
                    ['key' => 'curriculum', 'label' => 'Curriculum or board / পাঠ্যক্রম বা বোর্ড', 'type' => 'text', 'required' => true],
                    ['key' => 'typical_class_size', 'label' => 'Typical class size / সাধারণ শ্রেণির আকার', 'type' => 'number', 'required' => true],
                    ['key' => 'languages_used', 'label' => 'Languages used for instruction / শিক্ষাদানে ব্যবহৃত ভাষা', 'type' => 'tags', 'required' => false],
                    ['key' => 'available_support', 'label' => 'Available specialist or classroom support / বিশেষজ্ঞ বা শ্রেণিকক্ষ সহায়তা', 'type' => 'textarea', 'required' => false],
                    ['key' => 'development_priorities', 'label' => 'Professional development priorities / পেশাগত উন্নয়নের অগ্রাধিকার', 'type' => 'textarea', 'required' => false],
                    ['key' => 'assessment_approaches', 'label' => 'Assessment approaches currently used / বর্তমানে ব্যবহৃত মূল্যায়ন পদ্ধতি', 'type' => 'textarea', 'required' => false],
                    ['key' => 'technology_access', 'label' => 'Classroom technology and platform access / শ্রেণিকক্ষের প্রযুক্তি ও প্ল্যাটফর্ম সুবিধা', 'type' => 'textarea', 'required' => false],
                ]],
            ],
        ];
    }
}
