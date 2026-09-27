<?php

namespace App\Http\Controllers\Api;

use App\ExperienceStudio\ExperienceDesignAssistant;
use App\ExperienceStudio\ExperienceBlockType;
use App\Models\ExperienceBlueprint;
use App\Models\LearningGroup;
use Illuminate\Http\Request;

class ExperienceBlueprintController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeTeacher($request);
        $blueprints = ExperienceBlueprint::where('teacher_user_id', $request->user()->id)
            ->with(['blocks', 'learningGroup.profiles'])
            ->latest()->get();

        return $this->sendResponse('fetched', $blueprints);
    }

    public function store(Request $request)
    {
        $this->authorizeTeacher($request);
        $data = $this->validated($request);
        $group = $this->groupFor($request, $data['learning_group_id'] ?? null);
        $blueprint = ExperienceBlueprint::create(array_merge($data, [
            'teacher_user_id' => $request->user()->id,
            'learning_group_id' => $group?->id,
            'title' => $data['title'] ?: (($data['subject'] ?? 'Learning') . ' growth journey'),
            'language' => 'en',
            'status' => 'draft',
        ]));
        $this->storeBlocks($blueprint, $data['blocks'] ?? []);

        return $this->sendResponse('created', $blueprint->fresh()->load(['blocks', 'learningGroup.profiles']));
    }

    public function assist(Request $request, ExperienceDesignAssistant $assistant)
    {
        $this->authorizeTeacher($request);
        $data = $this->validated($request, false);
        $group = $this->groupFor($request, $data['learning_group_id'] ?? null);
        $context = $group ? $group->profiles->map(fn ($profile) => [
            'name' => $profile->full_name,
            'recent_strengths' => $profile->featureSnapshots()->latest('measured_at')->take(4)->get(['domain', 'feature'])->map(fn ($item) => $item->domain . ': ' . $item->feature),
        ])->values()->all() : [];

        return $this->sendResponse('generated', $assistant->draft(array_merge($data, ['student_context' => $context])));
    }

    public function publish(Request $request, ExperienceBlueprint $experienceBlueprint)
    {
        $this->authorizeTeacher($request);
        abort_unless($experienceBlueprint->teacher_user_id === $request->user()->id, 403);
        abort_unless($experienceBlueprint->blocks()->exists(), 422, 'Add at least one experience block before publishing.');
        $experienceBlueprint->update(['status' => 'published']);

        return $this->sendResponse('published', $experienceBlueprint->fresh()->load('blocks'));
    }

    private function validated(Request $request, bool $withBlocks = true): array
    {
        $rules = [
            'learning_group_id' => ['nullable', 'integer', 'exists:learning_groups,id'],
            'title' => ['nullable', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:80'],
            'grade_band' => ['nullable', 'string', 'max:40'],
            'focus_area' => ['nullable', 'string', 'max:120'],
            'objective' => ['required', 'string', 'min:12', 'max:1200'],
            'duration_weeks' => ['nullable', 'integer', 'min:1', 'max:16'],
            'success_criteria' => ['nullable', 'array', 'max:5'],
            'success_criteria.*' => ['string', 'max:240'],
        ];
        if ($withBlocks) {
            $rules += [
                'blocks' => ['nullable', 'array', 'max:24'],
                'blocks.*.week_number' => ['required', 'integer', 'min:1', 'max:16'],
                'blocks.*.block_type' => ['required', 'string', 'in:' . implode(',', ExperienceBlockType::values())],
                'blocks.*.title' => ['required', 'string', 'max:120'],
                'blocks.*.description' => ['nullable', 'string', 'max:1200'],
                'blocks.*.student_prompt' => ['nullable', 'string', 'max:1200'],
                'blocks.*.teacher_note' => ['nullable', 'string', 'max:1200'],
                'blocks.*.duration_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
                'blocks.*.personalization' => ['nullable', 'array'],
            ];
        }
        return $request->validate($rules);
    }

    private function storeBlocks(ExperienceBlueprint $blueprint, array $blocks): void
    {
        foreach ($blocks as $index => $block) {
            $blueprint->blocks()->create(array_merge($block, ['sort_order' => $index + 1]));
        }
    }

    private function groupFor(Request $request, ?int $groupId): ?LearningGroup
    {
        if (!$groupId) return null;
        return LearningGroup::where('teacher_user_id', $request->user()->id)->with('profiles')->findOrFail($groupId);
    }

    private function authorizeTeacher(Request $request): void
    {
        abort_unless(in_array($request->user()->user_type, ['teacher', 'admin'], true), 403);
    }
}
