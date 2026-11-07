<?php

use App\Models\ChildFeatureSnapshot;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->environment('testing')) {
            app(DemoSchoolSeeder::class)->run();
        }
    }

    public function down(): void
    {
        ChildFeatureSnapshot::where('source_type', 'like', 'demo_week_%')->delete();
    }
};
