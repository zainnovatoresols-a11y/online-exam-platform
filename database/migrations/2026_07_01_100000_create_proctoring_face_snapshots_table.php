<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proctoring_face_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('proctoring_event_id')
                ->nullable()
                ->constrained('proctoring_events')
                ->nullOnDelete();
            $table->string('violation_type', 40);
            $table->unsignedTinyInteger('face_count')->default(0);
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['test_attempt_id', 'captured_at'], 'pf_snapshots_attempt_captured_index');
            $table->index(['candidate_user_id', 'captured_at'], 'pf_snapshots_candidate_captured_index');
            $table->index('proctoring_event_id', 'pf_snapshots_event_index');
            $table->index('violation_type', 'pf_snapshots_violation_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proctoring_face_snapshots');
    }
};
