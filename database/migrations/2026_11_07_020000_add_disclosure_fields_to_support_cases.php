<?php

use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->json('professional_formulation')->nullable();
            $table->text('parent_summary')->nullable();
            $table->string('disclosure_status')->default('not_prepared');
            $table->json('disclosure_scope')->nullable();
            $table->string('child_assent_status')->default('not_requested');
            $table->timestamp('parent_consent_at')->nullable();
            $table->timestamp('parent_declined_at')->nullable();
        });
        if (! app()->environment('testing')) {
            app(DemoSchoolSeeder::class)->run();
        }
    }

    public function down(): void
    {
        Schema::table('support_cases', function (Blueprint $table) {
            $table->dropColumn(['professional_formulation', 'parent_summary', 'disclosure_status', 'disclosure_scope', 'child_assent_status', 'parent_consent_at', 'parent_declined_at']);
        });
    }
};
