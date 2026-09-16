<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ScenarioCoachController extends BaseController
{
    public function respond(Request $request)
    {
        $data = $request->validate([
            'scenario' => ['required', 'string', 'max:100'],
            'adult_response' => ['required', 'string', 'max:2000'],
            'language' => ['nullable', 'in:en,bn'],
        ]);

        $prompt = "You are a practical parent and teacher practice coach. Scenario: {$data['scenario']}. " .
            "Adult response: {$data['adult_response']}. Give a short child reaction, then coaching with: what worked, one better next sentence, and one next action. " .
            "Use " . ($data['language'] === 'bn' ? 'natural Bangla' : 'English') . ". Keep it supportive and specific.";

        foreach (['groq', 'gemini', 'anthropic'] as $provider) {
            $result = $this->{$provider}($prompt);
            if ($result) return $this->sendResponse('generated', ['provider' => $provider, 'reply' => $result]);
        }

        return $this->sendError('No scenario model is currently available.', [], 503);
    }

    private function groq(string $prompt): ?string
    {
        if (!env('GROQ_API_KEY2')) return null;
        $response = Http::withToken(env('GROQ_API_KEY2'))->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile', 'messages' => [['role' => 'user', 'content' => $prompt]], 'temperature' => 0.4,
        ]);
        return $response->successful() ? data_get($response->json(), 'choices.0.message.content') : null;
    }

    private function gemini(string $prompt): ?string
    {
        if (!env('GEMINI_API_KEY')) return null;
        $response = Http::post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . env('GEMINI_API_KEY'), [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ]);
        return $response->successful() ? data_get($response->json(), 'candidates.0.content.parts.0.text') : null;
    }

    private function anthropic(string $prompt): ?string
    {
        if (!env('ANTHROPIC_API_KEY')) return null;
        $response = Http::withHeaders(['x-api-key' => env('ANTHROPIC_API_KEY'), 'anthropic-version' => '2023-06-01'])->post('https://api.anthropic.com/v1/messages', [
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'), 'max_tokens' => 500, 'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);
        return $response->successful() ? data_get($response->json(), 'content.0.text') : null;
    }
}
