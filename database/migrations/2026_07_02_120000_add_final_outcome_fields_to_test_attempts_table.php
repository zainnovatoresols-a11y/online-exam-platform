<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('test_attempts', function (Blueprint $table): void {
            if (! Schema::hasColumn('test_attempts', 'score_passed')) {
                $table->boolean('score_passed')->nullable()->after('passed');
            }

            if (! Schema::hasColumn('test_attempts', 'proctoring_failed')) {
                $table->boolean('proctoring_failed')->default(false)->after('score_passed');
            }

            if (! Schema::hasColumn('test_attempts', 'suspicious_event_count')) {
                $table->unsignedInteger('suspicious_event_count')->default(0)->after('proctoring_failed');
            }

            if (! Schema::hasColumn('test_attempts', 'final_failure_reason')) {
                $table->string('final_failure_reason')->nullable()->after('suspicious_event_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_attempts', function (Blueprint $table): void {
            foreach ([
                'final_failure_reason',
                'suspicious_event_count',
                'proctoring_failed',
                'score_passed',
            ] as $column) {
                if (Schema::hasColumn('test_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
