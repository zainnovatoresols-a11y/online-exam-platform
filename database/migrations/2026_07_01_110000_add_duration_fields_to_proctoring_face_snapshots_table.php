<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proctoring_face_snapshots', function (Blueprint $table): void {
            $table->timestamp('started_at')->nullable()->after('captured_at');
            $table->timestamp('ended_at')->nullable()->after('started_at');
            $table->unsignedInteger('duration_seconds')->default(0)->after('ended_at');

            $table->index(['test_attempt_id', 'violation_type', 'started_at'], 'pf_snapshots_attempt_type_started_index');
        });
    }

    public function down(): void
    {
        Schema::table('proctoring_face_snapshots', function (Blueprint $table): void {
            $table->dropIndex('pf_snapshots_attempt_type_started_index');
            $table->dropColumn([
                'started_at',
                'ended_at',
                'duration_seconds',
            ]);
        });
    }
};
