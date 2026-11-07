<?php

use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_expert_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('service_path')->default('assessment_pending');
            $table->string('status')->default('new');
            $table->string('priority')->default('routine');
            $table->text('rationale')->nullable();
            $table->text('professional_notes')->nullable();
            $table->string('consent_status')->default('not_recorded');
            $table->date('next_review_at')->nullable();
            $table->timestamps();
        });

        if (! app()->environment('testing')) {
            app(DemoSchoolSeeder::class)->run();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_cases');
    }
};
