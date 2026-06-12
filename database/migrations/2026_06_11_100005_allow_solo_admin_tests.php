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
        if (! Schema::hasColumn('tests', 'created_by_id')) {
            Schema::table('tests', function (Blueprint $table): void {
                $table->foreignId('created_by_id')
                    ->nullable()
                    ->after('organization_id')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->index(['created_by_id', 'status']);
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tests MODIFY organization_id BIGINT UNSIGNED NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tests MODIFY organization_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('tests', 'created_by_id')) {
            Schema::table('tests', function (Blueprint $table): void {
                $table->dropIndex(['created_by_id', 'status']);
                $table->dropConstrainedForeignId('created_by_id');
            });
        }
    }
};
