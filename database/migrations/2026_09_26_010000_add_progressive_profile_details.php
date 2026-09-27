<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->json('details')->nullable()->after('relation_type');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->json('professional_details')->nullable()->after('is_onboarded');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', fn (Blueprint $table) => $table->dropColumn('details'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('professional_details'));
    }
};
