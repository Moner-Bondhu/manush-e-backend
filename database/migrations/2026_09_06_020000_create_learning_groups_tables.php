<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_user_id')->constrained('users');
            $table->string('name');
            $table->string('school_name')->nullable();
            $table->timestamps();
        });

        Schema::create('learning_group_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_group_id')->constrained();
            $table->foreignId('profile_id')->constrained();
            $table->timestamps();
            $table->unique(['learning_group_id', 'profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_group_profiles');
        Schema::dropIfExists('learning_groups');
    }
};
