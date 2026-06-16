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
        if (Schema::hasTable('code_execution_test_case_results')) {
            Schema::table('code_execution_test_case_results', function (Blueprint $table): void {
                $table->index(['code_execution_run_id', 'passed'], 'code_exec_tcr_run_passed_idx');
            });

            return;
        }

        Schema::create('code_execution_test_case_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('code_execution_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_test_case_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_hidden')->default(false);
            $table->string('status');
            $table->boolean('passed')->default(false);
            $table->text('input')->nullable();
            $table->text('expected_output')->nullable();
            $table->text('actual_output')->nullable();
            $table->text('stdout')->nullable();
            $table->text('stderr')->nullable();
            $table->text('compile_output')->nullable();
            $table->text('message')->nullable();
            $table->decimal('time', 8, 3)->nullable();
            $table->integer('memory')->nullable();
            $table->string('judge0_token')->nullable();
            $table->integer('judge0_status_id')->nullable();
            $table->string('judge0_status_description')->nullable();
            $table->timestamps();

            $table->index(['code_execution_run_id', 'passed'], 'code_exec_tcr_run_passed_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('code_execution_test_case_results');
    }
};
