<?php

namespace Tests\Feature;

use App\Models\GameDefinition;
use Database\Seeders\ExpandedGameLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpandedGameLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expanded_library_has_five_published_games_with_twelve_levels(): void
    {
        $this->seed(ExpandedGameLibrarySeeder::class);

        $games = GameDefinition::whereIn('slug', [
            'pattern-forge', 'route-builder', 'emotion-detective', 'story-lantern', 'number-harbor',
        ])->withCount('levels')->get();

        $this->assertCount(5, $games);
        $this->assertTrue($games->every(fn ($game) => $game->status === 'published' && $game->levels_count === 12));
    }
}
