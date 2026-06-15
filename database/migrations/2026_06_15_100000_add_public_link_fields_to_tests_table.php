<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table): void {
            $table->string('public_token', 64)->nullable()->unique()->after('starts_at');
            $table->boolean('public_access_enabled')->default(false)->after('public_token');
            $table->json('candidate_fields')->nullable()->after('public_access_enabled');
            $table->text('policy_text')->nullable()->after('candidate_fields');
        });

        DB::table('tests')
            ->whereNull('public_token')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id): void {
                do {
                    $token = Str::random(48);
                } while (DB::table('tests')->where('public_token', $token)->exists());

                DB::table('tests')
                    ->where('id', $id)
                    ->update([
                        'public_token' => $token,
                        'candidate_fields' => json_encode([]),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table): void {
            $table->dropColumn([
                'public_token',
                'public_access_enabled',
                'candidate_fields',
                'policy_text',
            ]);
        });
    }
};
