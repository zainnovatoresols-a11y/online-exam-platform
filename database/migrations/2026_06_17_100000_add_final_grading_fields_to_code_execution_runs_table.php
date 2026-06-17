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
        if (! Schema::hasTable('code_execution_runs')) {
            return;
        }

        if (! Schema::hasColumn('code_execution_runs', 'run_type')) {
            Schema::table('code_execution_runs', function (Blueprint $table): void {
                $table->string('run_type')->default('visible')->after('status');
            });
        }

        if (! Schema::hasColumn('code_execution_runs', 'score_awarded')) {
            Schema::table('code_execution_runs', function (Blueprint $table): void {
                $table->decimal('score_awarded', 8, 2)->nullable()->after('result_summary');
            });
        }

        if (! Schema::hasColumn('code_execution_runs', 'max_score')) {
            Schema::table('code_execution_runs', function (Blueprint $table): void {
                $table->decimal('max_score', 8, 2)->nullable()->after('score_awarded');
            });
        }

        if (! Schema::hasColumn('code_execution_runs', 'passed')) {
            Schema::table('code_execution_runs', function (Blueprint $table): void {
                $table->boolean('passed')->nullable()->after('max_score');
            });
        }

        if (! Schema::hasColumn('code_execution_runs', 'error_message')) {
            Schema::table('code_execution_runs', function (Blueprint $table): void {
                $table->text('error_message')->nullable()->after('passed');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('code_execution_runs')) {
            return;
        }

        foreach ([
            'error_message',
            'passed',
            'max_score',
            'score_awarded',
            'run_type',
        ] as $column) {
            if (! Schema::hasColumn('code_execution_runs', $column)) {
                continue;
            }

            Schema::table('code_execution_runs', function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        }
    }
};
