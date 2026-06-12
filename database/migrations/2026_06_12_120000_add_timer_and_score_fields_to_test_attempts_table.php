<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('test_attempts', 'organization_id')) {
            Schema::table('test_attempts', function (Blueprint $table): void {
                $table->unsignedBigInteger('organization_id')->nullable()->after('candidate_user_id')->index();
            });
        }

        if (! Schema::hasColumn('test_attempts', 'expires_at')) {
            Schema::table('test_attempts', function (Blueprint $table): void {
                $table->timestamp('expires_at')->nullable()->after('submitted_at');
            });
        }

        if (! Schema::hasColumn('test_attempts', 'max_score')) {
            Schema::table('test_attempts', function (Blueprint $table): void {
                $table->unsignedInteger('max_score')->default(0)->after('score');
            });
        }

        if (! Schema::hasColumn('test_attempts', 'percentage')) {
            Schema::table('test_attempts', function (Blueprint $table): void {
                $table->decimal('percentage', 5, 2)->nullable()->after('max_score');
            });
        }

        if (! Schema::hasColumn('test_attempts', 'passed')) {
            Schema::table('test_attempts', function (Blueprint $table): void {
                $table->boolean('passed')->nullable()->after('percentage');
            });
        }

        $attempts = DB::table('test_attempts')
            ->select(['id', 'test_id', 'started_at', 'total_marks'])
            ->get();

        foreach ($attempts as $attempt) {
            $test = DB::table('tests')
                ->select(['organization_id', 'duration_minutes'])
                ->where('id', $attempt->test_id)
                ->first();

            if (! $test) {
                continue;
            }

            DB::table('test_attempts')
                ->where('id', $attempt->id)
                ->update([
                    'organization_id' => $test->organization_id,
                    'expires_at' => $attempt->started_at
                        ? Carbon::parse($attempt->started_at)->addMinutes((int) $test->duration_minutes)
                        : null,
                    'max_score' => (int) $attempt->total_marks,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['passed', 'percentage', 'max_score', 'expires_at', 'organization_id'] as $column) {
            if (Schema::hasColumn('test_attempts', $column)) {
                Schema::table('test_attempts', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
