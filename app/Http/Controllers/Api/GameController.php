<?php

namespace App\Http\Controllers\Api;

use App\Games\GameFeatureExtractor;
use App\Games\GameProgressionService;
use App\Models\GameDefinition;
use App\Models\GameEvent;
use App\Models\GameSession;
use App\Models\GameProgress;
use Illuminate\Http\Request;

class GameController extends BaseController
{
    public function index(Request $request)
    {
        $data = $request->validate(['profile_id' => ['nullable', 'integer', 'exists:profiles,id']]);
        $profileId = $data['profile_id'] ?? null;
        if ($profileId) {
            $request->user()->profiles()->findOrFail($profileId);
        }
        $games = GameDefinition::where('status', 'published')
            ->orderBy('sort_order')
            ->get();

        return $this->sendResponse('fetched', $games->map(function ($game) use ($profileId) {
            $progress = $profileId ? GameProgress::where('profile_id', $profileId)->where('game_definition_id', $game->id)->first() : null;
            return array_merge($game->toArray(), ['progress' => $progress ? [
                'current_level' => $progress->current_level,
                'unlocked_level' => $progress->unlocked_level,
                'last_played_at' => $progress->last_played_at,
            ] : ['current_level' => 1, 'unlocked_level' => 1, 'last_played_at' => null]]);
        }));
    }

    public function start(Request $request, GameDefinition $game)
    {
        abort_unless($game->status === 'published', 404);
        $data = $request->validate(['profile_id' => ['required', 'integer', 'exists:profiles,id']]);
        $profile = $request->user()->profiles()->findOrFail($data['profile_id']);

        [$progress, $level] = app(GameProgressionService::class)->start($game, $profile->id);
        abort_unless($level, 422, 'This game has no configured levels.');
        $session = GameSession::create([
            'game_definition_id' => $game->id,
            'profile_id' => $profile->id,
            'status' => 'in_progress',
            'level' => $level->number,
            'challenge_config' => array_merge($level->parameters, ['form_seed' => bin2hex(random_bytes(8)), 'chapter' => $level->chapter]),
            'started_at' => now(),
        ]);

        return $this->sendResponse('created', ['session' => $session, 'progress' => $progress, 'challenge' => $session->challenge_config]);
    }

    public function complete(Request $request, GameSession $gameSession)
    {
        $profile = $request->user()->profiles()->findOrFail($gameSession->profile_id);
        abort_unless($profile->id === $gameSession->profile_id, 403);
        $data = $request->validate([
            'duration_ms' => ['required', 'integer', 'min:0', 'max:3600000'],
            'metrics' => ['required', 'array'],
        ]);

        abort_if($gameSession->status === 'completed', 409, 'This game session is already complete.');

        $gameSession->update([
            'status' => 'completed',
            'duration_ms' => $data['duration_ms'],
            'metrics' => $data['metrics'],
            'completed_at' => now(),
        ]);

        app(GameFeatureExtractor::class)->capture($gameSession->fresh()->load('game'));
        $progress = app(GameProgressionService::class)->complete($gameSession->fresh());

        return $this->sendResponse('updated', ['session' => $gameSession->fresh()->load('events'), 'progress' => $progress]);
    }

    public function recordEvents(Request $request, GameSession $gameSession)
    {
        $request->user()->profiles()->findOrFail($gameSession->profile_id);
        abort_if($gameSession->status !== 'in_progress', 409, 'Events can only be recorded during an active session.');
        $data = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:100'],
            'events.*.event_type' => ['required', 'string', 'max:64'],
            'events.*.payload' => ['required', 'array', 'max:25'],
            'events.*.occurred_at' => ['nullable', 'date'],
        ]);

        $events = collect($data['events'])->map(fn (array $event) => [
            'game_session_id' => $gameSession->id,
            'event_type' => $event['event_type'],
            'payload' => json_encode($event['payload']),
            'occurred_at' => $event['occurred_at'] ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();
        GameEvent::insert($events);

        return $this->sendResponse('recorded', ['count' => count($events)]);
    }

}
