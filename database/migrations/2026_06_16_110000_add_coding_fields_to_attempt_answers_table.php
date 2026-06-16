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
        Schema::table('attempt_answers', function (Blueprint $table): void {
            $table->string('language')->nullable()->after('selected_option_id');
            $table->longText('submitted_code')->nullable()->after('language');
            $table->timestamp('answered_at')->nullable()->after('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table): void {
            $table->dropColumn([
                'language',
                'submitted_code',
                'answered_at',
            ]);
        });
    }
};
