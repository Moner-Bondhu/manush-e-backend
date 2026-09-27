<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scales', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
            $table->string('domain')->default('wellbeing')->after('description');
            $table->json('constructs')->nullable()->after('domain');
            $table->string('evidence_type')->default('check_in')->after('constructs');
            $table->string('cadence')->default('as_needed')->after('evidence_type');
            $table->unsignedSmallInteger('estimated_minutes')->default(3)->after('cadence');
            $table->unsignedTinyInteger('minimum_age')->nullable()->after('estimated_minutes');
            $table->unsignedTinyInteger('maximum_age')->nullable()->after('minimum_age');
            $table->string('instrument_status')->default('original')->after('maximum_age');
            $table->string('source_name')->nullable()->after('instrument_status');
            $table->text('interpretation_note')->nullable()->after('source_name');
            $table->boolean('is_published')->default(true)->after('interpretation_note');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('construct')->nullable()->after('type');
            $table->boolean('reverse_scored')->default(false)->after('construct');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['construct', 'reverse_scored']);
        });
        Schema::table('scales', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn([
                'code', 'domain', 'constructs', 'evidence_type', 'cadence',
                'estimated_minutes', 'minimum_age', 'maximum_age',
                'instrument_status', 'source_name', 'interpretation_note', 'is_published',
            ]);
        });
    }
};
