<?php

namespace App\Services;

use App\Models\Profile;

class LongitudinalProfileService
{
    private const DIMENSIONS = [
        'attention_control' => ['attention', 'sustained_attention', 'selective_attention', 'inhibitory_control', 'processing_speed'],
        'executive_skills' => ['working_memory', 'sequencing', 'planning'],
        'reasoning_flexibility' => ['visual_scanning', 'pattern_reasoning', 'constraint_reasoning', 'numerical_reasoning'],
        'social_emotional_skills' => ['social_emotional', 'perspective_taking', 'emotion_recognition', 'social_reasoning'],
        'timing_coordination' => ['timing'],
    ];

    public function build(Profile $profile): array
    {
        $snapshots = $profile->featureSnapshots()->orderBy('measured_at')->get();
        $dimensions = collect(self::DIMENSIONS)->map(function ($domains, $dimension) use ($snapshots) {
            $items = $snapshots->whereIn('domain', $domains)->filter(fn ($item) => $this->normalize($item->feature, $item->value) !== null);
            $points = $items->groupBy(fn ($item) => $item->measured_at->format('Y-m-d'))
                ->map(fn ($day, $date) => [
                    'date' => $date,
                    'score' => (int) round($day->avg(fn ($item) => $this->normalize($item->feature, $item->value))),
                    'observations' => $day->count(),
                ])->values();
            if ($points->isEmpty()) return null;
            $baseline = $points->first()['score'];
            $current = $points->last()['score'];

            return [
                'key' => $dimension,
                'baseline' => $baseline,
                'current' => $current,
                'change' => $current - $baseline,
                'trend' => $current > $baseline + 3 ? 'improving' : ($current < $baseline - 3 ? 'variable_or_lower' : 'stable'),
                'sessions' => $points->count(),
                'observations' => $items->count(),
                'series' => $points,
            ];
        })->filter()->values();

        return [
            'score_kind' => 'practice_performance_index',
            'dimensions' => $dimensions,
            'overall' => $this->overall($dimensions),
            'interpretation' => 'Scores summarize performance in completed activities over time. They are not IQ, diagnosis, or a population percentile.',
            'minimum_for_trend' => 3,
        ];
    }

    public function reportedContext(Profile $profile): array
    {
        $details = $profile->details ?? [];

        return [
            'academic' => [
                'source' => 'school_and_teacher_reported',
                'strong_subjects' => array_values((array) ($details['strong_subjects'] ?? [])),
                'support_subjects' => array_values((array) ($details['support_subjects'] ?? [])),
                'attendance_context' => $details['attendance_context'] ?? null,
                'summary' => $details['recent_academic_summary'] ?? null,
            ],
            'health' => [
                'source' => 'parent_or_school_reported_not_clinically_verified',
                'sleep_hours' => $details['sleep_hours'] ?? null,
                'activity_days' => $details['activity_days'] ?? null,
                'health_context' => $details['health_context'] ?? null,
                'accessibility_needs' => $details['accessibility_needs'] ?? null,
            ],
        ];
    }

    private function overall($dimensions): ?array
    {
        if ($dimensions->isEmpty()) return null;
        $baseline = (int) round($dimensions->avg('baseline'));
        $current = (int) round($dimensions->avg('current'));

        return ['baseline' => $baseline, 'current' => $current, 'change' => $current - $baseline];
    }

    private function normalize(string $feature, float $value): ?float
    {
        if ($feature === 'median_response_ms') return null;
        if (in_array($feature, ['omission_rate', 'commission_rate'], true)) return max(0, min(100, 100 - $value));
        if ($feature === 'late_round_accuracy_change') return max(0, min(100, 50 + ($value / 2)));
        if (in_array($feature, ['sustained_attention', 'sequence_accuracy', 'planning_efficiency', 'perspective_taking'], true)) {
            return max(0, min(100, $value <= 1 ? $value * 100 : $value));
        }
        if (in_array($feature, ['task_accuracy', 'response_consistency'], true)) return max(0, min(100, $value));

        return null;
    }
}
