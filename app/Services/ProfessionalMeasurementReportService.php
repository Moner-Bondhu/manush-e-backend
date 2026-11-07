<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Response;

class ProfessionalMeasurementReportService
{
    private const FRAMEWORK = [
        'mental_health_and_wellbeing' => [
            'emotional_regulation' => ['constructs' => ['emotional_regulation']],
            'stress_load' => ['constructs' => ['stress_load']],
            'hope_and_future_orientation' => ['constructs' => ['hope']],
            'school_belonging' => ['constructs' => ['school_belonging']],
            'support_and_connectedness' => ['constructs' => ['support_access', 'family_connection']],
            'depressive_symptoms' => ['constructs' => [], 'required_instrument' => true],
            'anxiety_symptoms' => ['constructs' => [], 'required_instrument' => true],
            'functional_limitation' => ['constructs' => [], 'required_instrument' => true],
            'suicidality' => ['constructs' => [], 'required_protocol' => true],
            'care_seeking' => ['constructs' => [], 'required_instrument' => true],
        ],
        'behaviour_and_self_regulation' => [
            'attention_regulation' => ['constructs' => ['attention_regulation']],
            'inhibitory_control' => ['constructs' => ['inhibitory_control']],
            'transition_regulation' => ['constructs' => ['transition_regulation', 'screen_transition']],
            'frustration_tolerance' => ['constructs' => ['frustration_tolerance']],
            'recovery_after_distress' => ['constructs' => ['recovery']],
            'help_seeking' => ['constructs' => ['help_seeking']],
            'conduct_and_peer_difficulty' => ['constructs' => [], 'required_instrument' => true],
            'hyperactivity_inattention_symptoms' => ['constructs' => [], 'required_instrument' => true],
        ],
        'psychometric_and_cognitive_performance' => [
            'attention_control' => ['game_dimension' => 'attention_control'],
            'working_memory_and_planning' => ['game_dimension' => 'executive_skills'],
            'reasoning_flexibility' => ['game_dimension' => 'reasoning_flexibility'],
            'processing_speed' => ['constructs' => [], 'required_norms' => true],
            'general_intellectual_ability' => ['constructs' => [], 'required_norms' => true],
        ],
        'social_and_emotional_competence' => [
            'emotion_awareness' => ['constructs' => ['emotion_awareness']],
            'empathy' => ['constructs' => ['empathy', 'care_empathy']],
            'perspective_taking' => ['constructs' => ['perspective_taking']],
            'relationship_repair' => ['constructs' => ['relationship_repair']],
            'social_emotional_game_performance' => ['game_dimension' => 'social_emotional_skills'],
        ],
        'learning_and_student_potential' => [
            'curiosity' => ['constructs' => ['curiosity', 'intellectual_curiosity']],
            'creativity_and_imagination' => ['constructs' => ['creativity', 'creative_imagination']],
            'persistence' => ['constructs' => ['persistence']],
            'initiative_and_agency' => ['constructs' => ['initiative', 'learning_agency']],
            'planning_and_metacognition' => ['constructs' => ['planning', 'metacognition']],
            'preferred_expression' => ['constructs' => ['visual_expression', 'verbal_expression', 'hands_on_learning', 'preferred_expression']],
            'academic_achievement' => ['constructs' => [], 'reported_input' => true],
        ],
        'developmental_and_physical_context' => [
            'sleep_routine' => ['constructs' => ['sleep_routine']],
            'physical_activity' => ['constructs' => ['physical_activity']],
            'learning_routine' => ['constructs' => ['learning_routine']],
            'screen_transition' => ['constructs' => ['screen_transition']],
            'nutrition_and_health' => ['constructs' => [], 'reported_input' => true],
        ],
    ];

    public function build(Profile $profile, array $longitudinal): array
    {
        $responses = Response::where('profile_id', $profile->id)
            ->with(['question.scale', 'question.options', 'option'])->get();
        $scored = $responses->filter(fn ($response) => $response->question?->construct && $response->option)
            ->map(fn ($response) => $this->scoreResponse($response));
        $openEnded = $responses->filter(fn ($response) => filled($response->text_answer));
        $gameDimensions = collect($longitudinal['dimensions'] ?? [])->keyBy('key');

        $domains = collect(self::FRAMEWORK)->map(function ($constructs, $domain) use ($scored, $openEnded, $gameDimensions) {
            $rows = collect($constructs)->map(fn ($definition, $key) => $this->constructRow($key, $definition, $scored, $openEnded, $gameDimensions))->values();
            return [
                'key' => $domain,
                'coverage' => [
                    'measured' => $rows->whereIn('status', ['measured', 'partial'])->count(),
                    'not_assessed' => $rows->where('status', 'not_assessed')->count(),
                    'total' => $rows->count(),
                ],
                'constructs' => $rows,
            ];
        })->values();

        return [
            'framework_version' => 'manush_e_professional_measurement_matrix_v1',
            'domains' => $domains,
            'overall_coverage' => [
                'measured' => $domains->sum(fn ($domain) => $domain['coverage']['measured']),
                'not_assessed' => $domains->sum(fn ($domain) => $domain['coverage']['not_assessed']),
                'total' => $domains->sum(fn ($domain) => $domain['coverage']['total']),
            ],
            'instrument_register' => $responses->pluck('question.scale')->filter()->unique('id')->map(fn ($scale) => [
                'code' => $scale->code,
                'name' => $scale->name,
                'domain' => $scale->domain,
                'status' => $scale->instrument_status,
                'source' => $scale->source_name,
                'age_range' => [$scale->minimum_age, $scale->maximum_age],
                'interpretation_note' => $scale->interpretation_note,
            ])->values(),
            'professional_rules' => [
                'Questionnaire indices are descriptive within-instrument scores unless a named validated instrument and applicable norms are present.',
                'Open-ended responses are qualitative evidence and are never automatically converted to IQ, diagnosis, or risk.',
                'A blank or not-assessed construct is not a negative finding.',
                'Source disagreement is clinically informative and must not be averaged away.',
            ],
        ];
    }

    private function constructRow(string $key, array $definition, $scored, $openEnded, $gameDimensions): array
    {
        if (isset($definition['game_dimension'])) {
            $dimension = $gameDimensions->get($definition['game_dimension']);
            return $dimension ? [
                'key' => $key, 'status' => $dimension['sessions'] >= 3 ? 'measured' : 'partial',
                'score' => $dimension['current'], 'score_type' => 'game_practice_index',
                'observations' => $dimension['observations'], 'sources' => ['game'],
                'source_scores' => ['game' => $dimension['current']], 'agreement' => 'single_method',
                'caution' => 'Repeated task performance; no population norm or diagnostic interpretation.',
            ] : $this->notAssessed($key, 'No usable repeated game observations.');
        }

        $constructNames = $definition['constructs'] ?? [];
        $items = $scored->whereIn('construct', $constructNames);
        $qualitativeCount = $openEnded->filter(fn ($response) => in_array($response->question?->construct, $constructNames, true))->count();
        if ($items->isEmpty()) {
            $reason = isset($definition['required_protocol']) ? 'Requires a safeguarding protocol and age-appropriate validated administration.'
                : (isset($definition['required_norms']) ? 'Requires an appropriately normed standardized assessment.'
                    : (isset($definition['reported_input']) ? 'Available only as attributed school or family input; not scored here.'
                        : (isset($definition['required_instrument']) ? 'No applicable validated instrument has been administered.' : 'No scored evidence available.')));
            $row = $this->notAssessed($key, $reason);
            $row['qualitative_samples'] = $qualitativeCount;
            return $row;
        }

        $sourceScores = $items->groupBy('source')->map(fn ($source) => (int) round($source->avg('score')));
        $spread = $sourceScores->count() > 1 ? $sourceScores->max() - $sourceScores->min() : null;
        return [
            'key' => $key, 'status' => $items->count() >= 2 ? 'measured' : 'partial',
            'score' => (int) round($items->avg('score')), 'score_type' => 'descriptive_response_index',
            'observations' => $items->count(), 'qualitative_samples' => $qualitativeCount,
            'sources' => $sourceScores->keys()->values(), 'source_scores' => $sourceScores,
            'agreement' => $spread === null ? 'insufficient_for_concordance' : ($spread <= 15 ? 'convergent' : 'divergent_review_needed'),
            'caution' => $items->count() === 1 ? 'Single-item indicator; do not interpret as a scale.' : 'Descriptive score from current Manush-E items; not a clinical cut-off.',
        ];
    }

    private function scoreResponse($response): array
    {
        $options = $response->question->options;
        $minimum = (float) ($options->min('value') ?? 0);
        $maximum = (float) ($options->max('value') ?? 1);
        $score = $maximum > $minimum ? (($response->option->value - $minimum) / ($maximum - $minimum)) * 100 : 50;
        if ($response->question->reverse_scored) $score = 100 - $score;

        return [
            'construct' => $response->question->construct,
            'score' => round($score, 2),
            'source' => $response->question->scale->visible_to,
        ];
    }

    private function notAssessed(string $key, string $reason): array
    {
        return ['key' => $key, 'status' => 'not_assessed', 'score' => null, 'score_type' => null, 'observations' => 0, 'sources' => [], 'source_scores' => [], 'agreement' => 'not_applicable', 'caution' => $reason];
    }
}
