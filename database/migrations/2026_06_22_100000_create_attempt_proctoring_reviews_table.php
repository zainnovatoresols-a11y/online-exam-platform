<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_proctoring_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('needs_review');
            $table->string('risk_level', 40)->nullable();
            $table->json('reason_codes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique('test_attempt_id', 'apr_attempt_unique');
            $table->index(['test_id', 'status'], 'apr_test_status_index');
            $table->index(['organization_id', 'status'], 'apr_organization_status_index');
            $table->index(['reviewed_by_user_id', 'reviewed_at'], 'apr_reviewer_reviewed_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_proctoring_reviews');
    }
};
