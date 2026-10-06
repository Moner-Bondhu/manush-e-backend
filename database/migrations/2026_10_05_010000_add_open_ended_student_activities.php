<?php

use Database\Seeders\MeasurementLibrarySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->foreignId('option_id')->nullable()->change();
        });
        app(MeasurementLibrarySeeder::class)->run();
    }

    public function down(): void
    {
        \App\Models\Scale::whereIn('code', [
            'CHILD-OPEN-THINKING-STUDIO',
            'CHILD-OPEN-INTEREST-EXPLORER',
            'CHILD-OPEN-EMOTION-SCENARIOS',
        ])->get()->each(function ($scale) {
            $scale->questions()->each(fn ($question) => $question->delete());
            $scale->delete();
        });
    }
};
