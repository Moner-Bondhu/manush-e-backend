<?php

use Database\Seeders\ExpandedGameLibrarySeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(ExpandedGameLibrarySeeder::class)->run();
    }

    public function down(): void
    {
        $games = \App\Models\GameDefinition::whereIn('slug', [
            'pattern-forge', 'route-builder', 'emotion-detective', 'story-lantern', 'number-harbor',
        ])->get();
        foreach ($games as $game) {
            $game->levels()->delete();
            $game->delete();
        }
    }
};
