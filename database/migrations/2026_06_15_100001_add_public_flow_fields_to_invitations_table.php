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
        Schema::table('invitations', function (Blueprint $table): void {
            $table->timestamp('policy_accepted_at')->nullable()->after('accepted_at');
            $table->json('candidate_profile')->nullable()->after('policy_accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            $table->dropColumn([
                'policy_accepted_at',
                'candidate_profile',
            ]);
        });
    }
};
