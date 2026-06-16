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
        Schema::table('questions', function (Blueprint $table): void {
            $table->string('difficulty')->nullable()->after('order');
            $table->unsignedInteger('time_limit_ms')->nullable()->default(2000)->after('difficulty');
            $table->unsignedInteger('memory_limit_kb')->nullable()->default(128000)->after('time_limit_ms');
            $table->json('supported_languages')->nullable()->after('memory_limit_kb');
            $table->json('starter_code')->nullable()->after('supported_languages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn([
                'difficulty',
                'time_limit_ms',
                'memory_limit_kb',
                'supported_languages',
                'starter_code',
            ]);
        });
    }
};
