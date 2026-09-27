<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('portal_role')->default('parent')->after('user_type');
        });
        Schema::create('profile_user_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->string('relationship')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['profile_id', 'user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_user_assignments');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('portal_role'));
    }
};
