<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('test_attempts', 'candidate_user_id')) {
            return;
        }

        Schema::table('test_attempts', function (Blueprint $table): void {
            $table->unsignedBigInteger('candidate_user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('test_attempts', 'candidate_user_id')) {
            return;
        }

        DB::table('test_attempts')->whereNull('candidate_user_id')->delete();

        Schema::table('test_attempts', function (Blueprint $table): void {
            $table->unsignedBigInteger('candidate_user_id')->nullable(false)->change();
        });
    }
};
