<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experience_blueprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_user_id')->constrained('users');
            $table->foreignId('learning_group_id')->nullable()->constrained();
            $table->string('title');
            $table->string('subject')->nullable();
            $table->string('grade_band')->nullable();
            $table->string('focus_area')->nullable();
            $table->text('objective');
            $table->unsignedTinyInteger('duration_weeks')->default(6);
            $table->string('language', 8)->default('en');
            $table->string('status')->default('draft');
            $table->json('success_criteria')->nullable();
            $table->json('student_context')->nullable();
            $table->timestamps();
        });

        Schema::create('experience_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experience_blueprint_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->string('block_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('student_prompt')->nullable();
            $table->text('teacher_note')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->json('personalization')->nullable();
            $table->timestamps();
            $table->index(['experience_blueprint_id', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experience_blocks');
        Schema::dropIfExists('experience_blueprints');
    }
};
