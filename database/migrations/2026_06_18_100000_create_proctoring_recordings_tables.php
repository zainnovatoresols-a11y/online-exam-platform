<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proctoring_recordings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recording_type', 20);
            $table->string('status', 40)->default('pending_permission');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('denied_at')->nullable();
            $table->timestamp('last_chunk_at')->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->unsignedBigInteger('total_size_bytes')->default(0);
            $table->string('mime_type')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['test_attempt_id', 'recording_type'], 'pr_recordings_attempt_type_unique');
            $table->index(['candidate_user_id', 'created_at'], 'pr_recordings_candidate_created_index');
            $table->index('status', 'pr_recordings_status_index');
        });

        Schema::create('proctoring_recording_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proctoring_recording_id')
                ->constrained('proctoring_recordings')
                ->cascadeOnDelete();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('proctoring_event_id')
                ->nullable()
                ->constrained('proctoring_events')
                ->nullOnDelete();
            $table->string('recording_type', 20);
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->unsignedInteger('sequence');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['proctoring_recording_id', 'sequence'], 'pr_chunks_recording_sequence_unique');
            $table->index(['test_attempt_id', 'recording_type', 'uploaded_at'], 'pr_chunks_attempt_type_uploaded_index');
            $table->index(['candidate_user_id', 'uploaded_at'], 'pr_chunks_candidate_uploaded_index');
            $table->index('proctoring_event_id', 'pr_chunks_event_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proctoring_recording_chunks');
        Schema::dropIfExists('proctoring_recordings');
    }
};
