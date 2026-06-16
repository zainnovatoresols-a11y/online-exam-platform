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
        Schema::create('code_execution_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attempt_answer_id')->nullable()->constrained('attempt_answers')->nullOnDelete();
            $table->foreignId('candidate_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('language');
            $table->string('status')->default('pending');
            $table->longText('source_code');
            $table->json('result_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['test_attempt_id', 'question_id']);
            $table->index(['candidate_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('code_execution_runs');
    }
};
