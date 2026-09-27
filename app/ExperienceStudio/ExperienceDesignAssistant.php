<?php

namespace App\ExperienceStudio;

use Illuminate\Support\Facades\Http;

class ExperienceDesignAssistant
{
    public function draft(array $input): array
    {
        $fallback = $this->fallback($input);
        $prompt = "You are an instructional designer helping a school team create an English learning experience. " .
            "Return JSON only with keys: title, success_criteria (array of 3 short strings), blocks (array of exactly 3 objects with week_number, block_type, title, description, student_prompt, teacher_note, duration_minutes, personalization). " .
            "Use a varied mix of canonical block_type values only: play, pulse, practice, learn, scenario, create, reflect, connect, move, observe. " .
            "Make activities practical, inclusive, and teacher-led. Do not make clinical or diagnostic claims. " .
            "Context: " . json_encode($input, JSON_UNESCAPED_UNICODE);

        foreach (['groq', 'gemini', 'anthropic'] as $provider) {
            $text = $this->{$provider}($prompt);
            $decoded = $text ? json_decode($this->extractJson($text), true) : null;
            if (is_array($decoded) && isset($decoded['blocks']) && is_array($decoded['blocks'])) {
                return ['provider' => $provider, 'draft' => $this->normaliseDraft($fallback, $decoded)];
            }
        }

        return ['provider' => 'guided-template', 'draft' => $fallback];
    }

    private function fallback(array $input): array
    {
        $subject = $input['subject'] ?? 'Learning';
        $focus = $input['focus_area'] ?? 'confidence and participation';
        $objective = $input['objective'];

        return [
            'title' => $input['title'] ?? $subject . ' Growth Journey',
            'success_criteria' => [
                'Students can explain one strategy that helps them make progress.',
                'Students complete a meaningful practice task with the right level of support.',
                'Teachers can identify the next best learning step for each student.',
            ],
            'blocks' => [
                [
                    'week_number' => 1, 'block_type' => ExperienceBlockType::LEARN, 'title' => 'Start with strengths',
                    'description' => "Introduce {$objective} through a low-stakes, interest-led challenge.",
                    'student_prompt' => "Choose a way to explore {$subject} that feels interesting to you.",
                    'teacher_note' => "Notice which supports help students begin and stay involved.",
                    'duration_minutes' => 35, 'personalization' => ['pace' => 'choice of short or extended challenge', 'format' => 'visual, written, or collaborative'],
                ],
                [
                    'week_number' => 3, 'block_type' => ExperienceBlockType::PLAY, 'title' => 'Build the strategy',
                    'description' => "Use an interactive practice mission focused on {$focus}.",
                    'student_prompt' => 'Try one strategy, reflect on what changed, then choose your next challenge.',
                    'teacher_note' => 'Use a short small-group check-in for students who need a different entry point.',
                    'duration_minutes' => 40, 'personalization' => ['difficulty' => 'adjust after first attempt', 'language' => 'English-first content with clear visual scaffolds'],
                ],
                [
                    'week_number' => 6, 'block_type' => ExperienceBlockType::CREATE, 'title' => 'Apply and reflect',
                    'description' => 'Students apply their learning in a real-world mini project and reflect on progress.',
                    'student_prompt' => 'Show one thing you can now do, and describe the strategy that helped.',
                    'teacher_note' => 'Compare the starting point, work samples, and student reflection before choosing the next pathway.',
                    'duration_minutes' => 45, 'personalization' => ['product' => 'choice of presentation, demonstration, or portfolio entry'],
                ],
            ],
        ];
    }

    private function extractJson(string $text): string
    {
        if (preg_match('/\{.*\}/s', $text, $matches)) return $matches[0];
        return $text;
    }

    private function normaliseDraft(array $fallback, array $generated): array
    {
        $blocks = collect(array_slice($generated['blocks'], 0, 6))->values()->map(function ($block, $index) use ($fallback) {
            $base = $fallback['blocks'][$index] ?? $fallback['blocks'][0];
            if (!is_array($block)) return $base;

            $merged = array_merge($base, $block);
            $merged['week_number'] = max(1, min(16, (int) ($merged['week_number'] ?? $base['week_number'])));
            $merged['duration_minutes'] = max(5, min(240, (int) ($merged['duration_minutes'] ?? $base['duration_minutes'])));
            $merged['personalization'] = is_array($merged['personalization'] ?? null) ? $merged['personalization'] : $base['personalization'];
            foreach (['block_type', 'title', 'description', 'student_prompt', 'teacher_note'] as $key) {
                $merged[$key] = is_string($merged[$key] ?? null) && $merged[$key] !== '' ? $merged[$key] : $base[$key];
            }
            $merged['block_type'] = ExperienceBlockType::normalise($merged['block_type'], $base['block_type']);
            return $merged;
        })->all();

        return [
            'title' => is_string($generated['title'] ?? null) && $generated['title'] !== '' ? $generated['title'] : $fallback['title'],
            'success_criteria' => is_array($generated['success_criteria'] ?? null)
                ? array_values(array_filter(array_slice($generated['success_criteria'], 0, 5), 'is_string'))
                : $fallback['success_criteria'],
            'blocks' => count($blocks) ? $blocks : $fallback['blocks'],
        ];
    }

    private function groq(string $prompt): ?string
    {
        if (!env('GROQ_API_KEY2')) return null;
        $response = Http::timeout(20)->withToken(env('GROQ_API_KEY2'))->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'openai/gpt-oss-20b', 'messages' => [['role' => 'user', 'content' => $prompt]], 'temperature' => 0.3,
        ]);
        return $response->successful() ? data_get($response->json(), 'choices.0.message.content') : null;
    }

    private function gemini(string $prompt): ?string
    {
        if (!env('GEMINI_API_KEY')) return null;
        $response = Http::timeout(20)->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . env('GEMINI_API_KEY'), [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ]);
        return $response->successful() ? data_get($response->json(), 'candidates.0.content.parts.0.text') : null;
    }

    private function anthropic(string $prompt): ?string
    {
        if (!env('ANTHROPIC_API_KEY')) return null;
        $response = Http::timeout(20)->withHeaders(['x-api-key' => env('ANTHROPIC_API_KEY'), 'anthropic-version' => '2023-06-01'])->post('https://api.anthropic.com/v1/messages', [
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'), 'max_tokens' => 1100, 'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);
        return $response->successful() ? data_get($response->json(), 'content.0.text') : null;
    }
}
