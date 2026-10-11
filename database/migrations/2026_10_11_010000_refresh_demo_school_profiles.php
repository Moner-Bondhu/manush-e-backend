<?php

use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (! app()->environment('testing')) {
            app(DemoSchoolSeeder::class)->run();
        }
    }

    public function down(): void
    {
        // Demo records are intentionally retained when rolling back application code.
    }
};
