<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proctoring_recordings', function (Blueprint $table): void {
            $table->string('merged_disk')->nullable()->after('total_size_bytes');
            $table->string('merged_path')->nullable()->after('merged_disk');
            $table->string('merged_status', 40)->default('pending')->after('merged_path');
            $table->timestamp('merged_at')->nullable()->after('merged_status');
            $table->unsignedBigInteger('merged_size_bytes')->default(0)->after('merged_at');
            $table->text('merge_error')->nullable()->after('merged_size_bytes');

            $table->index('merged_status', 'pr_recordings_merged_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('proctoring_recordings', function (Blueprint $table): void {
            $table->dropIndex('pr_recordings_merged_status_index');
            $table->dropColumn([
                'merged_disk',
                'merged_path',
                'merged_status',
                'merged_at',
                'merged_size_bytes',
                'merge_error',
            ]);
        });
    }
};
